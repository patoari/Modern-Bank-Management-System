<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    public function create(User $user): bool          { return $user->hasPermissionTo('branches.create'); }
    public function update(User $user, Branch $b): bool { return $user->hasPermissionTo('branches.update'); }
    public function delete(User $user, Branch $b): bool { return $user->hasPermissionTo('branches.delete'); }
}
