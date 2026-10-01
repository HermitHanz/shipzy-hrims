<?php

namespace App\Actions\Access;

use App\Models\User;

class SetUserStatus extends AccessAction
{
    public const STATUSES = ['active', 'inactive', 'suspended'];

    /** Deactivate, suspend or reactivate an account. The EnsureUserIsActive middleware does the rest. */
    public function handle(User $actor, User $target, string $status): User
    {
        if (! in_array($status, self::STATUSES, true)) {
            $this->reject('status', 'Invalid status.');
        }

        $this->authorize($actor, 'deactivate', $target);

        $old = $target->status;

        if ($old === $status) {
            return $target;
        }

        $target->forceFill(['status' => $status])->save();

        $this->audit->log('user.status_changed', $target, ['status' => $old], ['status' => $status], $actor);

        return $target;
    }
}