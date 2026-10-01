<?php

namespace App\Support\Audit;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Single entry point for writing to audit_logs.
 *
 *   app(AuditLogger::class)->log('user.role_assigned', $user, ['roles' => $old], ['roles' => $new]);
 *
 * Action names are dot-separated: <subject>.<event>  (auth.login, role.updated, ...)
 */
class AuditLogger
{
    private const REDACTED_KEYS = ['password', 'password_confirmation', 'remember_token', 'current_password'];

    public function log(
        string $action,
        ?Model $target = null,
        ?array $old = null,
        ?array $new = null,
        ?User $actor = null,
    ): void {
        $actor ??= Auth::user();
        $request = app()->runningInConsole() ? null : request();

        DB::table('audit_logs')->insert([
            'actor_id' => $actor?->getKey(),
            'action' => $action,
            'target_type' => $target ? Str::snake(class_basename($target)) : null,
            'target_id' => $target?->getKey(),
            'old_values' => $old !== null ? json_encode($this->redact($old)) : null,
            'new_values' => $new !== null ? json_encode($this->redact($new)) : null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request ? Str::limit((string) $request->userAgent(), 255, '') : null,
            'created_at' => now(),
        ]);
    }

    /** Never write secrets into the audit trail. */
    private function redact(array $values): array
    {
        foreach ($values as $key => $value) {
            if (in_array($key, self::REDACTED_KEYS, true)) {
                $values[$key] = '[redacted]';
            } elseif (is_array($value)) {
                $values[$key] = $this->redact($value);
            }
        }

        return $values;
    }
}