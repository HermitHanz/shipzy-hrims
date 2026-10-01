<?php

namespace App\Actions\Access;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class UpdateRole extends AccessAction
{
    /**
     * The role's name (slug) never changes. System roles keep their level.
     *
     * @param  array{label: string, description?: string|null, level: int, permissions?: array<int, string>}  $data
     */
    public function handle(User $actor, Role $role, array $data): Role
    {
        $this->authorize($actor, 'update', $role);

        $level = (int) $data['level'];

        if ($role->is_system) {
            if ($level !== (int) $role->level) {
                $this->reject('level', 'The level of a system role cannot be changed.');
            }
        } elseif (! $this->guard->canSetRoleLevel($actor, $level)) {
            $this->reject('level', 'You can only set a level below your own.');
        }

        $names = $this->permissionNames($data['permissions'] ?? []);
        $current = $role->permissions->pluck('name')->all();

        $this->assertGrantable($actor, array_values(array_diff($names, $current)));

        $old = [
            'label' => $role->label,
            'description' => $role->description,
            'level' => (int) $role->level,
            'permissions' => $current,
        ];

        DB::transaction(function () use ($actor, $role, $data, $level, $names, $old) {
            $role->forceFill([
                'label' => $data['label'],
                'description' => $data['description'] ?? null,
                'level' => $level,
            ])->save();

            $role->syncPermissions($names);

            $this->audit->log('role.updated', $role, $old, [
                'label' => $data['label'],
                'description' => $data['description'] ?? null,
                'level' => $level,
                'permissions' => $names,
            ], $actor);
        });

        return $role->refresh();
    }
}