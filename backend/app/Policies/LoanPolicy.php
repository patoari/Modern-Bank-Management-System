<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;

class LoanPolicy
{
    public function viewAny(User $user): bool { return $user->hasPermissionTo('loans.view'); }
    public function view(User $user, Loan $loan): bool
    {
        if ($user->hasPermissionTo('loans.view')) return true;
        return $user->customer?->id === $loan->customer_id;
    }
    public function create(User $user): bool   { return $user->hasPermissionTo('loans.create'); }
    public function update(User $user, Loan $loan): bool { return $user->hasPermissionTo('loans.assess'); }
    public function approve(User $user, Loan $loan): bool { return $user->hasPermissionTo('loans.approve'); }
    public function disburse(User $user, Loan $loan): bool { return $user->hasPermissionTo('loans.disburse'); }
}
