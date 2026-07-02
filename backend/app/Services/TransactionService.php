<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AmlAlert;
use App\Models\AuditLog;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    private const HIGH_VALUE_THRESHOLD = 10000;
    private const AML_THRESHOLD        = 10000;

    public function list(array $filters, int $perPage = 15)
    {
        $query = Transaction::with(['fromAccount.customer', 'toAccount.customer', 'initiatedBy', 'branch']);

        if (!empty($filters['account_id'])) {
            $id = $filters['account_id'];
            $query->where(fn($q) => $q->where('from_account_id', $id)->orWhere('to_account_id', $id));
        }
        if (!empty($filters['transaction_type']))   $query->where('transaction_type', $filters['transaction_type']);
        if (!empty($filters['transaction_status'])) $query->where('transaction_status', $filters['transaction_status']);
        if (!empty($filters['from_date']))          $query->whereDate('value_date', '>=', $filters['from_date']);
        if (!empty($filters['to_date']))            $query->whereDate('value_date', '<=', $filters['to_date']);
        if (!empty($filters['min_amount']))         $query->where('amount', '>=', $filters['min_amount']);
        if (!empty($filters['max_amount']))         $query->where('amount', '<=', $filters['max_amount']);

        return $query->latest()->paginate($perPage);
    }

    public function deposit(array $data, int $byUser): Transaction
    {
        return DB::transaction(function () use ($data, $byUser) {
            $account = Account::lockForUpdate()->findOrFail($data['account_id']);

            if ($account->account_status !== 'active') {
                throw new \Exception("Account {$account->account_number} is not active.");
            }

            $fee   = $this->calculateFee('deposit', $data['amount']);
            $total = $data['amount'];

            $txn = Transaction::create([
                'transaction_ref'    => $this->generateRef(),
                'transaction_type'   => 'deposit',
                'transaction_mode'   => $data['mode'] ?? 'cash',
                'to_account_id'      => $account->id,
                'amount'             => $data['amount'],
                'currency_code'      => $account->currency_code,
                'transaction_fee'    => $fee,
                'total_amount'       => $total,
                'transaction_status' => 'completed',
                'description'        => $data['description'] ?? 'Cash deposit',
                'initiated_by'       => $byUser,
                'branch_id'          => $data['branch_id'] ?? $account->branch_id,
                'value_date'         => now()->toDateString(),
                'posting_date'       => now(),
                'requires_approval'  => false,
            ]);

            $account->increment('available_balance', $data['amount']);
            $account->increment('ledger_balance', $data['amount']);
            $account->update(['last_transaction_date' => now()->toDateString()]);

            $this->checkAml($txn, $account);
            $this->logAudit($byUser, 'deposit', $txn);

            return $txn->load(['toAccount.customer', 'initiatedBy']);
        });
    }

    public function withdraw(array $data, int $byUser): Transaction
    {
        return DB::transaction(function () use ($data, $byUser) {
            $account = Account::lockForUpdate()->findOrFail($data['account_id']);

            if ($account->account_status !== 'active') {
                throw new \Exception("Account {$account->account_number} is not active.");
            }

            $fee       = $this->calculateFee('withdrawal', $data['amount']);
            $total     = $data['amount'] + $fee;
            $minBal    = $account->minimum_balance ?? 0;

            if (($account->available_balance - $total) < $minBal) {
                throw new \Exception('Insufficient balance.');
            }

            $requiresApproval = $data['amount'] >= self::HIGH_VALUE_THRESHOLD;

            $txn = Transaction::create([
                'transaction_ref'    => $this->generateRef(),
                'transaction_type'   => 'withdrawal',
                'transaction_mode'   => $data['mode'] ?? 'cash',
                'from_account_id'    => $account->id,
                'amount'             => $data['amount'],
                'currency_code'      => $account->currency_code,
                'transaction_fee'    => $fee,
                'total_amount'       => $total,
                'transaction_status' => $requiresApproval ? 'pending_approval' : 'completed',
                'description'        => $data['description'] ?? 'Cash withdrawal',
                'initiated_by'       => $byUser,
                'branch_id'          => $data['branch_id'] ?? $account->branch_id,
                'value_date'         => now()->toDateString(),
                'posting_date'       => now(),
                'requires_approval'  => $requiresApproval,
                'approval_status'    => $requiresApproval ? 'pending' : null,
            ]);

            if (! $requiresApproval) {
                $account->decrement('available_balance', $total);
                $account->decrement('ledger_balance', $total);
                $account->update(['last_transaction_date' => now()->toDateString()]);
            }

            $this->checkAml($txn, $account);
            $this->logAudit($byUser, 'withdrawal', $txn);

            return $txn->load(['fromAccount.customer', 'initiatedBy']);
        });
    }

    public function transfer(array $data, int $byUser): Transaction
    {
        return DB::transaction(function () use ($data, $byUser) {
            $from = Account::lockForUpdate()->findOrFail($data['from_account_id']);
            $to   = Account::lockForUpdate()->where('account_number', $data['to_account_number'])->firstOrFail();

            if ($from->account_status !== 'active') throw new \Exception('Source account is not active.');
            if ($to->account_status   !== 'active') throw new \Exception('Destination account is not active.');

            $fee   = $this->calculateFee('transfer', $data['amount']);
            $total = $data['amount'] + $fee;

            if ($from->available_balance < $total) throw new \Exception('Insufficient balance.');

            $requiresApproval = $data['amount'] >= self::HIGH_VALUE_THRESHOLD;

            $txn = Transaction::create([
                'transaction_ref'    => $this->generateRef(),
                'transaction_type'   => 'transfer',
                'transaction_mode'   => $data['mode'] ?? 'internal',
                'from_account_id'    => $from->id,
                'to_account_id'      => $to->id,
                'amount'             => $data['amount'],
                'currency_code'      => $from->currency_code,
                'transaction_fee'    => $fee,
                'total_amount'       => $total,
                'transaction_status' => $requiresApproval ? 'pending_approval' : 'completed',
                'description'        => $data['description'] ?? 'Fund transfer',
                'initiated_by'       => $byUser,
                'branch_id'          => $from->branch_id,
                'value_date'         => now()->toDateString(),
                'posting_date'       => now(),
                'requires_approval'  => $requiresApproval,
                'approval_status'    => $requiresApproval ? 'pending' : null,
            ]);

            if (! $requiresApproval) {
                $from->decrement('available_balance', $total);
                $from->decrement('ledger_balance', $total);
                $to->increment('available_balance', $data['amount']);
                $to->increment('ledger_balance', $data['amount']);
                $from->update(['last_transaction_date' => now()->toDateString()]);
                $to->update(['last_transaction_date' => now()->toDateString()]);
            }

            $this->checkAml($txn, $from);
            $this->logAudit($byUser, 'transfer', $txn);

            return $txn->load(['fromAccount.customer', 'toAccount.customer', 'initiatedBy']);
        });
    }

    public function approve(Transaction $txn, int $byUser): Transaction
    {
        return DB::transaction(function () use ($txn, $byUser) {
            if ($txn->transaction_status !== 'pending_approval') {
                throw new \Exception('Transaction is not pending approval.');
            }
            $txn->update([
                'transaction_status' => 'completed',
                'approval_status'    => 'approved',
                'approved_by'        => $byUser,
            ]);

            // Apply balance changes now
            if ($txn->transaction_type === 'withdrawal' && $txn->from_account_id) {
                $from = Account::lockForUpdate()->find($txn->from_account_id);
                $from->decrement('available_balance', $txn->total_amount);
                $from->decrement('ledger_balance', $txn->total_amount);
                $from->update(['last_transaction_date' => now()->toDateString()]);
            } elseif ($txn->transaction_type === 'transfer') {
                $from = Account::lockForUpdate()->find($txn->from_account_id);
                $to   = Account::lockForUpdate()->find($txn->to_account_id);
                $from->decrement('available_balance', $txn->total_amount);
                $from->decrement('ledger_balance', $txn->total_amount);
                $to->increment('available_balance', $txn->amount);
                $to->increment('ledger_balance', $txn->amount);
                $from->update(['last_transaction_date' => now()->toDateString()]);
                $to->update(['last_transaction_date' => now()->toDateString()]);
            }

            $this->logAudit($byUser, 'approve_transaction', $txn);
            return $txn->fresh();
        });
    }

    public function reverse(Transaction $txn, string $reason, int $byUser): Transaction
    {
        return DB::transaction(function () use ($txn, $reason, $byUser) {
            if ($txn->transaction_status !== 'completed') {
                throw new \Exception('Only completed transactions can be reversed.');
            }
            if ($txn->is_reversed) {
                throw new \Exception('Transaction already reversed.');
            }

            $reversal = Transaction::create([
                'transaction_ref'    => $this->generateRef(),
                'transaction_type'   => 'reversal',
                'transaction_mode'   => $txn->transaction_mode,
                'from_account_id'    => $txn->to_account_id,
                'to_account_id'      => $txn->from_account_id,
                'amount'             => $txn->amount,
                'currency_code'      => $txn->currency_code,
                'transaction_fee'    => 0,
                'total_amount'       => $txn->amount,
                'transaction_status' => 'completed',
                'description'        => "Reversal of {$txn->transaction_ref}: {$reason}",
                'initiated_by'       => $byUser,
                'branch_id'          => $txn->branch_id,
                'value_date'         => now()->toDateString(),
                'posting_date'       => now(),
                'reversal_of'        => $txn->id,
                'requires_approval'  => false,
            ]);

            $txn->update(['is_reversed' => true]);

            // Reverse balances
            if ($txn->to_account_id) {
                $toAcc = Account::find($txn->to_account_id);
                $toAcc->decrement('available_balance', $txn->amount);
                $toAcc->decrement('ledger_balance', $txn->amount);
            }
            if ($txn->from_account_id) {
                $fromAcc = Account::find($txn->from_account_id);
                $fromAcc->increment('available_balance', $txn->amount);
                $fromAcc->increment('ledger_balance', $txn->amount);
            }

            $this->logAudit($byUser, 'reverse_transaction', $reversal);
            return $reversal->fresh();
        });
    }

    private function calculateFee(string $type, float $amount): float
    {
        return match ($type) {
            'transfer'   => $amount >= 10000 ? 10.00 : ($amount >= 1000 ? 5.00 : 0.00),
            'withdrawal' => 0.00,
            default      => 0.00,
        };
    }

    private function checkAml(Transaction $txn, Account $account): void
    {
        if ($txn->amount >= self::AML_THRESHOLD) {
            AmlAlert::create([
                'alert_number'   => 'AML' . strtoupper(uniqid()),
                'customer_id'    => $account->customer_id,
                'transaction_id' => $txn->id,
                'account_id'     => $account->id,
                'alert_type'     => 'high_value_transaction',
                'severity'       => $txn->amount >= 50000 ? 'high' : 'medium',
                'alert_status'   => 'open',
                'rule_triggered' => 'HIGH_VALUE_THRESHOLD',
                'description'    => "Transaction amount {$txn->amount} exceeds AML threshold",
                'amount_involved'=> $txn->amount,
            ]);
        }
    }

    private function logAudit(int $userId, string $action, Transaction $txn): void
    {
        AuditLog::create([
            'user_id'    => $userId,
            'action'     => $action,
            'module'     => 'transactions',
            'record_id'  => $txn->id,
            'record_type'=> Transaction::class,
            'new_values' => $txn->toArray(),
            'status'     => 'success',
            'description'=> "Transaction {$txn->transaction_ref}",
            'created_at' => now(),
        ]);
    }

    private function generateRef(): string
    {
        return 'TXN' . date('ymd') . strtoupper(substr(uniqid(), -6)) . rand(10, 99);
    }
}
