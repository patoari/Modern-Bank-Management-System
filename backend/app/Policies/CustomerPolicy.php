<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission(['customers.view']);
    }

    public function view(User $user, Customer $customer): bool
    {
        if ($user->hasPermissionTo('customers.view')) return true;
        // Customer can view their own record
        return $user->customer?->id === $customer->id;
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('customers.create');
    }

    public function update(User $user, Customer $customer): bool
    {
        if ($user->hasPermissionTo('customers.update')) return true;
        return $user->customer?->id === $customer->id;
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('customers.delete');
    }

    public function kyc(User $user, Customer $customer): bool
    {
        return $user->hasPermissionTo('customers.kyc_verify');
    }
}
