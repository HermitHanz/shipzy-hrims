<?php

namespace App\Actions\Access;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateUserAccount extends AccessAction
{
    /**
     * @param  array{name: string, email: string, employee_id?: int|null, roles: array<int, string>, permissions?: array<int, string>}  $data
     * @return array{0: User, 1: string}  the new user and the one-time temporary password
     */
    public function handle(User $actor, array $data): array
    {
        $this->authorize($actor, 'create', User::class);

        $roles = $this->rolesFor($actor, $data['roles'] ?? []);
        $permissions = $this->permissionNames($data['permissions'] ?? []);
        $this->assertGrantable($actor, $permissions);

        $employeeId = $data['employee_id'] ?? null;

        if ($employeeId && User::where('employee_id', $employeeId)->exists()) {
            $this->reject('employee_id', 'This employee already has a login.');
        }

        if (User::where('email', $data['email'])->exists()) {
            $this->reject('email', 'That email address is already in use.');
        }

        $temporaryPassword = Str::password(14, symbols: false);

        $user = DB::transaction(function () use ($actor, $data, $employeeId, $roles, $permissions, $temporaryPassword) {
            $user = new User();
            $user->forceFill([
                'name' => $data['name'],
                'email' => $data['email'],
                'employee_id' => $employeeId,
                'password' => Hash::make($temporaryPassword),
                'status' => 'active',
                'must_change_password' => true,
                'email_verified_at' => now(),
            ])->save();

            $user->syncRoles($roles);

            if ($permissions !== []) {
                $user->syncPermissions($permissions);
            }

            $this->audit->log('user.created', $user, null, [
                'email' => $user->email,
                'employee_id' => $employeeId,
                'roles' => $roles->pluck('name')->all(),
                'permissions' => $permissions,
            ], $actor);

            return $user;
        });

        return [$user, $temporaryPassword];
    }
}