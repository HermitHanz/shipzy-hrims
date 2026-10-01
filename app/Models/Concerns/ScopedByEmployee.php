<?php

namespace App\Models\Concerns;

use App\Models\User;
use App\Support\Access\ScopeResolver;
use Illuminate\Database\Eloquent\Builder;

/**
 * Adds ->visibleTo($user, 'module.resource.view') to a model.
 *
 * - On Employee itself, nothing else is needed.
 * - On a model that belongs to an employee (leave request, attendance record...),
 *   override employeeScopeRelation() to return the relation name, e.g. 'employee'.
 */
trait ScopedByEmployee
{
    /** Relation to Employee, or null when the model IS the employee. */
    protected function employeeScopeRelation(): ?string
    {
        return null;
    }

    public function scopeVisibleTo(Builder $query, User $user, string $permissionBase): Builder
    {
        $resolver = app(ScopeResolver::class);
        $scope = $resolver->resolve($user, $permissionBase);

        if ($scope === ScopeResolver::ALL) {
            return $query;
        }

        $me = $user->employee;

        if ($scope === ScopeResolver::NONE || ! $me) {
            return $query->whereRaw('1 = 0');
        }

        $relation = $this->employeeScopeRelation();

        if ($relation === null) {
            return $resolver->constrain($query, $scope, $me);
        }

        return $query->whereHas($relation, fn (Builder $q) => $resolver->constrain($q, $scope, $me));
    }
}