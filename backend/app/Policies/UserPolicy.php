<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool    { return $user->hasPermissionTo('users.view'); }
    public function view(User $user, User $model): bool { return $user->hasPermissionTo('users.view') || $user->id === $model->id; }
    public function create(User $user): bool     { return $user->hasPermissionTo('users.create'); }
    public function update(User $user, User $model): bool { return $user->hasPermissionTo('users.update') || $user->id === $model->id; }
    public function delete(User $user, User $model): bool { return $user->hasPermissionTo('users.delete'); }
    public function assignRoles(User $user, User $model): bool { return $user->hasPermissionTo('users.assign_roles'); }
}
