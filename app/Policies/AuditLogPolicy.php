<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('system.audit-log.view');
    }

    public function view(User $user, AuditLog $log): bool
    {
        return $user->can('system.audit-log.view');
    }

    public function export(User $user): bool
    {
        return $user->can('system.audit-log.export');
    }
}