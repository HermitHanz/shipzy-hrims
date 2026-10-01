<?php

namespace App\Actions\Access;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class CreateRole extends AccessAction
{
    /**
     * @param  array{name: string, label: string, description?: string|null, level: int, permissions?: array<int, string>}  $data
     */
    public function handle(User $actor, array $data): Role
    {
        $this->authorize($actor, 'create', Role::class);

        $level = (int) $data['level'];

        if (! $this->guard->canSetRoleLevel($actor, $level)) {
            $this->reject('level', 'You can only create roles below your own level.');
        }

        $permissions = $this->permissionNames($data['permissions'] ?? []);
        $this->assertGrantable($actor, $permissions);

        if (Role::where('guard_name', self::GUARD)->where('name', $data['name'])->exists()) {
            $this->reject('name', 'A role with that name already exists.');
        }

        return DB::transaction(function () use ($actor, $data, $level, $permissions) {
            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => self::GUARD,
                'label' => $data['label'],
                'description' => $data['description'] ?? null,
                'level' => $level,
                'is_system' => false,
            ]);

            $role->syncPermissions($permissions);

            $this->audit->log('role.created', $role, null, [
                'name' => $role->name,
                'label' => $role->label,
                'level' => $level,
                'permissions' => $permissions,
            ], $actor);

            return $role;
        });
    }
}