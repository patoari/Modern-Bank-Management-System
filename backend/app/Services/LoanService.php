<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Loan;
use App\Models\LoanRepaymentSchedule;
use App\Models\Transaction;
use App\Models\Account;
use Illuminate\Support\Facades\DB;

class LoanService
{
    public function list(array $filters, int $perPage = 15)
    {
        $query = Loan::with(['customer.user', 'loanProduct', 'branch'])->whereNull('deleted_at');

        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where('loan_account_number', 'like', "%{$s}%")
                  ->orWhereHas('customer', fn($q) =>
                      $q->where('first_name', 'like', "%{$s}%")->orWhere('customer_id', 'like', "%{$s}%")
                  );
        }
        if (!empty($filters['loan_status']))  $query->where('loan_status', $filters['loan_status']);
        if (!empty($filters['customer_id']))  $query->where('customer_id', $filters['customer_id']);
        if (!empty($filters['branch_id']))    $query->where('branch_id', $filters['branch_id']);

        return $query->latest()->paginate($perPage);
    }

    public function apply(array $data, int $byUser): Loan
    {
        return DB::transaction(function () use ($data, $byUser) {
            $loan = Loan::create([
                'loan_account_number' => $this->generateLoanNumber(),
                'loan_product_id'     => $data['loan_product_id'],
                'customer_id'         => $data['customer_id'],
                'branch_id'           => $data['branch_id'],
                'principal_amount'    => $data['principal_amount'],
                'interest_rate'       => $data['interest_rate'],
                'interest_type'       => $data['interest_type'] ?? 'reducing_balance',
                'tenure_months'       => $data['tenure_months'],
                'purpose'             => $data['purpose'] ?? null,
                'collateral_type'     => $data['collateral_type'] ?? null,
                'collateral_value'    => $data['collateral_value'] ?? null,
                'loan_status'         => 'applied',
                'application_date'    => now()->toDateString(),
                'outstanding_principal'=> $data['principal_amount'],
                'outstanding_interest' => 0,
                'total_outstanding'    => $data['principal_amount'],
                'loan_officer_id'      => $byUser,
            ]);

            // Calculate EMI
            $emi = $this->calculateEmi(
                $data['principal_amount'],
                $data['interest_rate'],
                $data['tenure_months']
            );
            $loan->update(['emi_amount' => $emi]);

            AuditLog::create([
                'user_id'    => $byUser,
                'action'     => 'loan_apply',
                'module'     => 'loans',
                'record_id'  => $loan->id,
                'record_type'=> Loan::class,
                'new_values' => $loan->toArray(),
                'status'     => 'success',
                'description'=> "Loan {$loan->loan_account_number} applied",
                'created_at' => now(),
            ]);

            return $loan->load(['customer.user', 'loanProduct', 'branch']);
        });
    }

    public function approve(Loan $loan, array $data, int $byUser): Loan
    {
        $loan->update([
            'loan_status'      => 'approved',
            'sanctioned_amount'=> $data['sanctioned_amount'] ?? $loan->principal_amount,
            'interest_rate'    => $data['interest_rate'] ?? $loan->interest_rate,
            'sanction_date'    => now()->toDateString(),
            'credit_manager_id'=> $byUser,
        ]);
        $emi = $this->calculateEmi($loan->sanctioned_amount, $loan->interest_rate, $loan->tenure_months);
        $loan->update(['emi_amount' => $emi]);
        $this->logAudit($byUser, 'loan_approve', $loan);
        return $loan->fresh();
    }

    public function reject(Loan $loan, string $reason, int $byUser): Loan
    {
        $loan->update(['loan_status' => 'rejected', 'rejection_reason' => $reason]);
        $this->logAudit($byUser, 'loan_reject', $loan);
        return $loan->fresh();
    }

    public function disburse(Loan $loan, int $byUser): Loan
    {
        return DB::transaction(function () use ($loan, $byUser) {
            if ($loan->loan_status !== 'approved') {
                throw new \Exception('Loan must be approved before disbursement.');
            }

            $account = Account::findOrFail($loan->repayment_account_id ?? Account::where('customer_id', $loan->customer_id)->where('account_status', 'active')->value('id'));

            // Credit loan amount to customer account
            Transaction::create([
                'transaction_ref'    => 'LOAN' . date('ymd') . strtoupper(substr(uniqid(), -5)),
                'transaction_type'   => 'loan_disbursement',
                'transaction_mode'   => 'internal',
                'to_account_id'      => $account->id,
                'amount'             => $loan->sanctioned_amount,
                'currency_code'      => $account->currency_code,
                'transaction_fee'    => 0,
                'total_amount'       => $loan->sanctioned_amount,
                'transaction_status' => 'completed',
                'description'        => "Loan disbursement for {$loan->loan_account_number}",
                'initiated_by'       => $byUser,
                'branch_id'          => $loan->branch_id,
                'value_date'         => now()->toDateString(),
                'posting_date'       => now(),
                'requires_approval'  => false,
            ]);

            $account->increment('available_balance', $loan->sanctioned_amount);
            $account->increment('ledger_balance', $loan->sanctioned_amount);

            $loan->update([
                'loan_status'       => 'disbursed',
                'disbursed_amount'  => $loan->sanctioned_amount,
                'disbursement_date' => now()->toDateString(),
                'maturity_date'     => now()->addMonths($loan->tenure_months)->toDateString(),
                'repayment_account_id' => $account->id,
            ]);

            // Generate repayment schedule
            $this->generateRepaymentSchedule($loan);

            $this->logAudit($byUser, 'loan_disburse', $loan);
            return $loan->fresh(['repaymentSchedule', 'customer.user']);
        });
    }

    public function generateRepaymentSchedule(Loan $loan): void
    {
        $principal     = $loan->disbursed_amount;
        $monthlyRate   = $loan->interest_rate / 12 / 100;
        $emi           = $loan->emi_amount;
        $outstanding   = $principal;
        $startDate     = now()->addMonth();

        LoanRepaymentSchedule::where('loan_id', $loan->id)->delete();

        for ($i = 1; $i <= $loan->tenure_months; $i++) {
            $interest  = round($outstanding * $monthlyRate, 2);
            $principal = round($emi - $interest, 2);
            if ($i === $loan->tenure_months) {
                $principal = $outstanding; // final installment clears balance
            }
            $outstanding -= $principal;

            LoanRepaymentSchedule::create([
                'loan_id'             => $loan->id,
                'installment_number'  => $i,
                'due_date'            => $startDate->copy()->addMonths($i - 1)->toDateString(),
                'principal_amount'    => $principal,
                'interest_amount'     => $interest,
                'penalty_amount'      => 0,
                'emi_amount'          => $emi,
                'payment_status'      => 'pending',
            ]);
        }
    }

    public function calculateEmi(float $principal, float $annualRate, int $months): float
    {
        $r = $annualRate / 12 / 100;
        if ($r == 0) return round($principal / $months, 2);
        $emi = $principal * $r * pow(1 + $r, $months) / (pow(1 + $r, $months) - 1);
        return round($emi, 2);
    }

    private function generateLoanNumber(): string
    {
        return 'LN' . date('y') . str_pad(Loan::withTrashed()->count() + 1, 8, '0', STR_PAD_LEFT);
    }

    private function logAudit(int $userId, string $action, Loan $loan): void
    {
        AuditLog::create([
            'user_id'    => $userId,
            'action'     => $action,
            'module'     => 'loans',
            'record_id'  => $loan->id,
            'record_type'=> Loan::class,
            'new_values' => $loan->fresh()->toArray(),
            'status'     => 'success',
            'description'=> "Loan {$loan->loan_account_number} — {$action}",
            'created_at' => now(),
        ]);
    }
}
