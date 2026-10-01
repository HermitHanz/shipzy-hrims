<?php

namespace App\Policies;

use App\Models\User;
use App\Support\Access\AccessGuard;

class UserPolicy
{
    public function __construct(private AccessGuard $guard)
    {
    }

    public function viewAny(User $actor): bool
    {
        return $actor->can('system.user.view');
    }

    public function view(User $actor, User $target): bool
    {
        return $actor->can('system.user.view') && $this->guard->canView($actor, $target);
    }

    public function create(User $actor): bool
    {
        return $actor->can('system.user.create');
    }

    public function update(User $actor, User $target): bool
    {
        return $actor->can('system.user.edit') && $this->guard->outranks($actor, $target);
    }

    public function deactivate(User $actor, User $target): bool
    {
        return $actor->can('system.user.deactivate')
            && $this->guard->outranks($actor, $target)
            && ! $this->guard->isLastActiveSuperAdmin($target);
    }

    public function delete(User $actor, User $target): bool
    {
        return $actor->can('system.user.delete')
            && $this->guard->outranks($actor, $target)
            && ! $this->guard->isLastActiveSuperAdmin($target);
    }

    /** Assign roles and direct permissions to a user. */
    public function manageAccess(User $actor, User $target): bool
    {
        return $actor->can('system.role.assign') && $this->guard->outranks($actor, $target);
    }
}