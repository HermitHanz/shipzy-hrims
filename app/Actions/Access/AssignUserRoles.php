<?php

namespace App\Actions\Access;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class AssignUserRoles extends AccessAction
{
    /** @param  array<int, string>  $roleNames */
    public function handle(User $actor, User $target, array $roleNames): User
    {
        $this->authorize($actor, 'manageAccess', $target);

        $roles = $this->rolesFor($actor, $roleNames);
        $newNames = $roles->pluck('name')->all();

        if ($this->guard->wouldRemoveLastSuperAdmin($target, $newNames)) {
            $this->reject('roles', 'At least one active Super Admin must remain.');
        }

        $old = $target->getRoleNames()->all();

        DB::transaction(function () use ($actor, $target, $roles, $old, $newNames) {
            $target->syncRoles($roles);

            $this->audit->log('user.roles_changed', $target, ['roles' => $old], ['roles' => $newNames], $actor);
        });

        return $target->refresh();
    }
}