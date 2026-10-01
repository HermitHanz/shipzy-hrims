<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Access\AccessGuard;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function __construct(private AccessGuard $guard)
    {
    }

    public function viewAny(User $actor): bool
    {
        return $actor->can('system.role.view');
    }

    public function view(User $actor, Role $role): bool
    {
        return $actor->can('system.role.view');
    }

    public function create(User $actor): bool
    {
        return $actor->can('system.role.create');
    }

    /** Edit a role's details and its permission list. */
    public function update(User $actor, Role $role): bool
    {
        return $actor->can('system.role.edit') && $this->guard->canManageRole($actor, $role);
    }

    public function delete(User $actor, Role $role): bool
    {
        return $actor->can('system.role.delete')
            && ! $role->is_system
            && $this->guard->canManageRole($actor, $role);
    }
}