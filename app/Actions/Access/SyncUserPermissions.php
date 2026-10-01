<?php

namespace App\Actions\Access;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class SyncUserPermissions extends AccessAction
{
    /**
     * Replace a user's DIRECT permissions (role permissions are untouched).
     * Anything newly added must be something the actor holds; removing is always allowed.
     *
     * @param  array<int, string>  $permissionNames
     */
    public function handle(User $actor, User $target, array $permissionNames): User
    {
        $this->authorize($actor, 'manageAccess', $target);

        $names = $this->permissionNames($permissionNames);
        $current = $target->getDirectPermissions()->pluck('name')->all();

        $this->assertGrantable($actor, array_values(array_diff($names, $current)));

        DB::transaction(function () use ($actor, $target, $names, $current) {
            $target->syncPermissions($names);

            $this->audit->log(
                'user.permissions_changed',
                $target,
                ['permissions' => $current],
                ['permissions' => $names],
                $actor,
            );
        });

        return $target->refresh();
    }
}