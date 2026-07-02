<?php

namespace App\Policies;

use App\Models\AmlAlert;
use App\Models\AuditLog;
use App\Models\User;

class AuditPolicy
{
    public function viewLogs(User $user, string $model): bool
    {
        return $user->hasPermissionTo('audit.view_logs');
    }

    public function viewAml(User $user, string $model): bool
    {
        return $user->hasPermissionTo('audit.aml_monitoring');
    }

    public function reviewAml(User $user, AmlAlert $alert): bool
    {
        return $user->hasPermissionTo('audit.aml_monitoring');
    }
}
