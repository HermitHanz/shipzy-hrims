<?php

namespace App\Actions\Access;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class DeleteRole extends AccessAction
{
    public function handle(User $actor, Role $role): void
    {
        $this->authorize($actor, 'delete', $role);

        if ($role->users()->exists()) {
            $this->reject('role', 'Reassign the users who hold this role before deleting it.');
        }

        DB::transaction(function () use ($actor, $role) {
            $snapshot = [
                'name' => $role->name,
                'label' => $role->label,
                'level' => (int) $role->level,
                'permissions' => $role->permissions->pluck('name')->all(),
            ];

            $this->audit->log('role.deleted', $role, $snapshot, null, $actor);

            $role->delete();
        });
    }
}