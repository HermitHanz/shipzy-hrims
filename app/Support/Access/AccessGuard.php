<?php

namespace App\Support\Access;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role;

/**
 * Anti-escalation rules for managing users, roles and permissions.
 * Pure PHP checks (no Gate calls) so the invariants can't be bypassed.
 *
 * Rules:
 *  - You can only manage users at a strictly lower level than yours (Super Admin outranks everyone).
 *  - Nobody manages their own access.
 *  - You can only assign roles below your own level.
 *  - You can only grant permissions you hold yourself.
 *  - The super-admin role is immutable, and the last active Super Admin can't be removed.
 */
class AccessGuard
{
    public const SUPER_ADMIN = 'super-admin';

    public function isSuperAdmin(User $user): bool
    {
        return $user->hasRole(self::SUPER_ADMIN);
    }

    /** Highest level among the user's roles (0 if none). */
    public function levelOf(User $user): int
    {
        return (int) $user->loadMissing('roles')->roles->max('level');
    }

    /** May $actor change $target's account, roles or permissions? */
    public function outranks(User $actor, User $target): bool
    {
        if ($actor->is($target)) {
            return false;
        }

        return $this->isSuperAdmin($actor) || $this->levelOf($actor) > $this->levelOf($target);
    }

    /** Viewing is more relaxed: same level is fine (HR Admins can see each other). */
    public function canView(User $actor, User $target): bool
    {
        return $actor->is($target)
            || $this->isSuperAdmin($actor)
            || $this->levelOf($actor) >= $this->levelOf($target);
    }

    public function canManageRole(User $actor, Role $role): bool
    {
        if ($role->name === self::SUPER_ADMIN) {
            return false;
        }

        return $this->isSuperAdmin($actor) || (int) $role->level < $this->levelOf($actor);
    }

    public function canAssignRole(User $actor, Role $role): bool
    {
        return $this->isSuperAdmin($actor) || (int) $role->level < $this->levelOf($actor);
    }

    /** Highest level $actor may give a role they create or edit (-1 if none). 100 is reserved for super-admin. */
    public function maxSettableLevel(User $actor): int
    {
        return $this->isSuperAdmin($actor) ? 99 : $this->levelOf($actor) - 1;
    }

    /**
     * Every level the actor may set, for level dropdowns. Empty if none.
     *
     * @return array<int, int>
     */
    public function settableLevels(User $actor): array
    {
        $max = $this->maxSettableLevel($actor);

        return $max < 0 ? [] : range(0, $max);
    }

    public function canSetRoleLevel(User $actor, int $level): bool
    {
        return $level >= 0 && $level <= $this->maxSettableLevel($actor);
    }

    /**
     * Roles this actor may assign, lowest level first. Use it to fill role pickers so the
     * UI and canAssignRole() can't drift apart.
     *
     * @return Collection<int, Role>
     */
    public function assignableRoles(User $actor): Collection
    {
        return Role::where('guard_name', 'web')
            ->orderBy('level')
            ->orderBy('name')
            ->get()
            ->filter(fn (Role $role) => $this->canAssignRole($actor, $role))
            ->values();
    }

    /**
     * Accounts this actor may see in lists. Mirrors canView(): Super Admin sees everyone,
     * everyone else sees accounts at or below their own level.
     */
    public function viewableUsers(User $actor): Builder
    {
        $query = User::query();

        if ($this->isSuperAdmin($actor)) {
            return $query;
        }

        $level = $this->levelOf($actor);

        return $query->whereDoesntHave('roles', fn (Builder $roles) => $roles
            ->where($roles->getModel()->qualifyColumn('level'), '>', $level));
    }

    public function canGrantPermission(User $actor, string $permission): bool
    {
        return $this->isSuperAdmin($actor) || $actor->can($permission);
    }

    /** Returns the permission names from the list that $actor is NOT allowed to grant. */
    public function ungrantable(User $actor, iterable $permissionNames): array
    {
        $blocked = [];

        foreach ($permissionNames as $name) {
            if (! $this->canGrantPermission($actor, $name)) {
                $blocked[] = $name;
            }
        }

        return $blocked;
    }

    public function isLastActiveSuperAdmin(User $user): bool
    {
        if (! $this->isSuperAdmin($user) || $user->status !== 'active') {
            return false;
        }

        return User::role(self::SUPER_ADMIN)->where('status', 'active')->count() <= 1;
    }

    /** Use before syncing roles: would this change strip the last Super Admin? */
    public function wouldRemoveLastSuperAdmin(User $target, array $newRoleNames): bool
    {
        return $this->isLastActiveSuperAdmin($target) && ! in_array(self::SUPER_ADMIN, $newRoleNames, true);
    }
}