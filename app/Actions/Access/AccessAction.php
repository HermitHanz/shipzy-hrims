<?php

namespace App\Actions\Access;

use App\Models\User;
use App\Support\Access\AccessGuard;
use App\Support\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Base for every action that changes users, roles or permissions.
 *
 * Conventions:
 *  - Policy denials throw AuthorizationException (renders as 403).
 *  - Bad payloads (roles/permissions/levels the actor may not use) throw ValidationException,
 *    keyed by field so forms can show the message next to the input.
 */
abstract class AccessAction
{
    protected const GUARD = 'web';

    public function __construct(
        protected AccessGuard $guard,
        protected AuditLogger $audit,
    ) {
    }

    protected function authorize(User $actor, string $ability, mixed $arguments): void
    {
        Gate::forUser($actor)->authorize($ability, $arguments);
    }

    protected function reject(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }

    /**
     * Resolve role names to models and make sure the actor may assign every one.
     *
     * @param  array<int, string>  $names
     * @return Collection<int, Role>
     */
    protected function rolesFor(User $actor, array $names): Collection
    {
        $names = array_values(array_unique($names));

        if ($names === []) {
            $this->reject('roles', 'Select at least one role.');
        }

        $roles = Role::where('guard_name', self::GUARD)->whereIn('name', $names)->get();

        if ($roles->count() !== count($names)) {
            $this->reject('roles', 'One or more selected roles do not exist.');
        }

        foreach ($roles as $role) {
            if (! $this->guard->canAssignRole($actor, $role)) {
                $this->reject('roles', 'You cannot assign the ' . ($role->label ?? $role->name) . ' role.');
            }
        }

        return $roles;
    }

    /**
     * Validate that every permission name exists; returns the de-duplicated list.
     *
     * @param  array<int, string>  $names
     * @return array<int, string>
     */
    protected function permissionNames(array $names): array
    {
        $names = array_values(array_unique($names));

        $found = Permission::where('guard_name', self::GUARD)->whereIn('name', $names)->count();

        if ($found !== count($names)) {
            $this->reject('permissions', 'One or more selected permissions do not exist.');
        }

        return $names;
    }

    /** Fail if the actor is trying to grant permissions they don't hold themselves. */
    protected function assertGrantable(User $actor, array $permissionNames): void
    {
        $blocked = $this->guard->ungrantable($actor, $permissionNames);

        if ($blocked !== []) {
            $this->reject('permissions', 'You cannot grant: ' . implode(', ', $blocked));
        }
    }
}