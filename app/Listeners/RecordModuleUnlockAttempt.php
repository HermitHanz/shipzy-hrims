<?php

namespace App\Listeners;

use App\Events\ModuleUnlockAttempted;
use App\Support\Audit\AuditLogger;

/**
 * Records every attempt to unlock a password-protected module (like the audit log).
 *
 * Keep this listener synchronous (do NOT implement ShouldQueue): the audit logger reads the
 * signed-in user, IP address and browser from the current request, which a queue worker
 * wouldn't have.
 */
class RecordModuleUnlockAttempt
{
    public function __construct(private AuditLogger $audit)
    {
    }

    public function handle(ModuleUnlockAttempted $event): void
    {
        if ($event->successful) {
            $action = 'security.module_unlocked';
        } elseif ($event->throttled) {
            $action = 'security.module_unlock_throttled';
        } else {
            $action = 'security.module_unlock_failed';
        }

        // The module key and the outcome only. Never log the password that was entered.
        $this->audit->log($action, null, null, ['module' => $event->module]);
    }
}