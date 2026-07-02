<?php

namespace App\Services;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class AccountService
{
    public function list(array $filters, int $perPage = 15)
    {
        $query = Account::with(['customer.user', 'accountType', 'branch'])->whereNull('deleted_at');

        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where('account_number', 'like', "%{$s}%")
                  ->orWhere('account_title', 'like', "%{$s}%")
                  ->orWhereHas('customer', fn($q) =>
                      $q->where('first_name', 'like', "%{$s}%")
                        ->orWhere('last_name', 'like', "%{$s}%")
                        ->orWhere('customer_id', 'like', "%{$s}%")
                  );
        }
        if (!empty($filters['account_status']))  $query->where('account_status', $filters['account_status']);
        if (!empty($filters['account_type_id'])) $query->where('account_type_id', $filters['account_type_id']);
        if (!empty($filters['branch_id']))        $query->where('branch_id', $filters['branch_id']);
        if (!empty($filters['customer_id']))      $query->where('customer_id', $filters['customer_id']);

        return $query->latest()->paginate($perPage);
    }

    public function open(array $data, int $createdBy): Account
    {
        return DB::transaction(function () use ($data, $createdBy) {
            $accountType = AccountType::findOrFail($data['account_type_id']);

            $account = Account::create([
                'account_number'    => $this->generateAccountNumber(),
                'account_type_id'   => $accountType->id,
                'customer_id'       => $data['customer_id'],
                'branch_id'         => $data['branch_id'],
                'currency_code'     => $data['currency_code'] ?? 'USD',
                'account_title'     => $data['account_title'],
                'account_status'    => 'active',
                'opening_date'      => now()->toDateString(),
                'available_balance' => $data['initial_deposit'] ?? 0,
                'ledger_balance'    => $data['initial_deposit'] ?? 0,
                'hold_amount'       => 0,
                'interest_rate'     => $data['interest_rate'] ?? $accountType->interest_rate,
                'minimum_balance'   => $data['minimum_balance'] ?? $accountType->min_balance,
            ]);

            // Record initial deposit as transaction if any
            if (!empty($data['initial_deposit']) && $data['initial_deposit'] > 0) {
                Transaction::create([
                    'transaction_ref'    => $this->generateTxnRef(),
                    'transaction_type'   => 'deposit',
                    'transaction_mode'   => 'cash',
                    'to_account_id'      => $account->id,
                    'amount'             => $data['initial_deposit'],
                    'currency_code'      => $account->currency_code,
                    'transaction_fee'    => 0,
                    'total_amount'       => $data['initial_deposit'],
                    'transaction_status' => 'completed',
                    'description'        => 'Initial deposit on account opening',
                    'initiated_by'       => $createdBy,
                    'branch_id'          => $account->branch_id,
                    'value_date'         => now()->toDateString(),
                    'posting_date'       => now(),
                    'requires_approval'  => false,
                ]);
            }

            AuditLog::create([
                'user_id'    => $createdBy,
                'action'     => 'account_open',
                'module'     => 'accounts',
                'record_id'  => $account->id,
                'record_type'=> Account::class,
                'new_values' => $account->toArray(),
                'status'     => 'success',
                'description'=> "Account {$account->account_number} opened",
                'created_at' => now(),
            ]);

            return $account->load(['customer.user', 'accountType', 'branch']);
        });
    }

    public function freeze(Account $account, string $reason, int $byUser): Account
    {
        $account->update(['account_status' => 'frozen']);
        AuditLog::create([
            'user_id'    => $byUser,
            'action'     => 'account_freeze',
            'module'     => 'accounts',
            'record_id'  => $account->id,
            'record_type'=> Account::class,
            'new_values' => ['reason' => $reason],
            'status'     => 'success',
            'description'=> "Account {$account->account_number} frozen: {$reason}",
            'created_at' => now(),
        ]);
        return $account;
    }

    public function unfreeze(Account $account, int $byUser): Account
    {
        $account->update(['account_status' => 'active']);
        AuditLog::create([
            'user_id'    => $byUser,
            'action'     => 'account_unfreeze',
            'module'     => 'accounts',
            'record_id'  => $account->id,
            'record_type'=> Account::class,
            'status'     => 'success',
            'description'=> "Account {$account->account_number} unfrozen",
            'created_at' => now(),
        ]);
        return $account;
    }

    public function close(Account $account, string $reason, int $byUser): Account
    {
        if ($account->available_balance != 0) {
            throw new \Exception('Account balance must be zero before closing.');
        }
        $account->update(['account_status' => 'closed', 'closed_date' => now(), 'closed_by' => $byUser]);
        AuditLog::create([
            'user_id'    => $byUser,
            'action'     => 'account_close',
            'module'     => 'accounts',
            'record_id'  => $account->id,
            'record_type'=> Account::class,
            'new_values' => ['reason' => $reason],
            'status'     => 'success',
            'description'=> "Account {$account->account_number} closed: {$reason}",
            'created_at' => now(),
        ]);
        return $account;
    }

    public function getStatement(Account $account, string $from, string $to)
    {
        return Transaction::with(['initiatedBy', 'fromAccount', 'toAccount'])
            ->where(fn($q) => $q->where('from_account_id', $account->id)
                                ->orWhere('to_account_id', $account->id))
            ->whereBetween('value_date', [$from, $to])
            ->where('transaction_status', 'completed')
            ->orderBy('value_date')
            ->get();
    }

    private function generateAccountNumber(): string
    {
        do {
            $number = '10' . str_pad(random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        } while (Account::where('account_number', $number)->exists());
        return $number;
    }

    private function generateTxnRef(): string
    {
        return 'TXN' . strtoupper(uniqid()) . rand(100, 999);
    }
}
