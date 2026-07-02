<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function viewAny(User $user): bool  { return $user->hasPermissionTo('transactions.view'); }
    public function view(User $user, Transaction $txn): bool { return $user->hasPermissionTo('transactions.view'); }
    public function deposit(User $user): bool  { return $user->hasPermissionTo('transactions.cash_deposit'); }
    public function withdraw(User $user): bool { return $user->hasPermissionTo('transactions.cash_withdrawal'); }
    public function transfer(User $user): bool { return $user->hasPermissionTo('transactions.fund_transfer'); }
    public function approve(User $user, Transaction $txn): bool { return $user->hasPermissionTo('transactions.approve'); }
    public function reverse(User $user, Transaction $txn): bool { return $user->hasPermissionTo('transactions.reverse'); }
}
