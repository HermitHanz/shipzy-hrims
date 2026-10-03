<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('organization.department.view');
    }

    public function view(User $user, Department $department): bool
    {
        return $user->can('organization.department.view');
    }

    public function create(User $user): bool
    {
        return $user->can('organization.department.create');
    }

    public function update(User $user, Department $department): bool
    {
        return $user->can('organization.department.edit');
    }

    public function deactivate(User $user, Department $department): bool
    {
        return $user->can('organization.department.deactivate');
    }

    public function delete(User $user, Department $department): bool
    {
        return $user->can('organization.department.delete');
    }
}