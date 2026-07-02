<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\User;

class AccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('accounts.view');
    }

    public function view(User $user, Account $account): bool
    {
        if ($user->hasPermissionTo('accounts.view')) return true;
        return $user->customer?->id === $account->customer_id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('accounts.create');
    }

    public function update(User $user, Account $account): bool
    {
        return $user->hasPermissionTo('accounts.update');
    }

    public function freeze(User $user, Account $account): bool
    {
        return $user->hasPermissionTo('accounts.freeze');
    }

    public function close(User $user, Account $account): bool
    {
        return $user->hasPermissionTo('accounts.close');
    }
}
