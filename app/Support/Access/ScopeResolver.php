<?php

namespace App\Support\Access;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Turns scoped permissions into data filters.
 *
 * A "permission base" is a permission key without its scope suffix, e.g.
 * 'leave.request.view' resolves against:
 *   leave.request.view-all | view-department | view-team | view-own
 *
 * The widest scope the user holds wins. Narrower scopes always include the
 * user's own record (a manager on 'team' scope still sees themselves).
 */
class ScopeResolver
{
    public const ALL = 'all';
    public const DEPARTMENT = 'department';
    public const TEAM = 'team';
    public const OWN = 'own';
    public const NONE = 'none';

    /** Widest to narrowest. */
    private const ORDER = [self::ALL, self::DEPARTMENT, self::TEAM, self::OWN];

    /** Returns 'all' | 'department' | 'team' | 'own' | 'none'. */
    public function resolve(User $user, string $permissionBase): string
    {
        foreach (self::ORDER as $scope) {
            if ($user->can("{$permissionBase}-{$scope}")) {
                return $scope;
            }
        }

        return self::NONE;
    }

    /** Apply the user's scope to a query on the employees table (or a relation to it). */
    public function constrain(Builder $employeeQuery, string $scope, Employee $me): Builder
    {
        $model = $employeeQuery->getModel();
        $id = $model->qualifyColumn('id');
        $managerId = $model->qualifyColumn('manager_id');
        $departmentId = $model->qualifyColumn('department_id');

        return $employeeQuery->where(function (Builder $q) use ($scope, $me, $id, $managerId, $departmentId) {
            $q->where($id, $me->id); // own record is always included

            if ($scope === self::TEAM) {
                $q->orWhere($managerId, $me->id);
            }

            if ($scope === self::DEPARTMENT) {
                $q->orWhere($departmentId, $me->department_id);
            }
        });
    }

    /** Single-record check, for policies. Pass the employee the record belongs to. */
    public function allows(User $user, string $permissionBase, Employee $target): bool
    {
        $scope = $this->resolve($user, $permissionBase);

        if ($scope === self::ALL) {
            return true;
        }

        $me = $user->employee;

        if ($scope === self::NONE || ! $me) {
            return false;
        }

        return match ($scope) {
            self::OWN => $target->id === $me->id,
            self::TEAM => $target->id === $me->id || $target->manager_id === $me->id,
            self::DEPARTMENT => $target->id === $me->id || $target->department_id === $me->department_id,
            default => false,
        };
    }
}