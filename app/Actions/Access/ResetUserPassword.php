<?php

namespace App\Actions\Access;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResetUserPassword extends AccessAction
{
    /** @return string the one-time temporary password (shown to HR once, never stored or logged) */
    public function handle(User $actor, User $target): string
    {
        $this->authorize($actor, 'update', $target);

        $temporaryPassword = Str::password(14, symbols: false);

        $target->forceFill([
            'password' => Hash::make($temporaryPassword),
            'must_change_password' => true,
            'password_changed_at' => null,
        ])->save();

        $this->audit->log('user.password_reset', $target, null, null, $actor);

        return $temporaryPassword;
    }
}