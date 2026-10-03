<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('organization.branch.view');
    }

    public function view(User $user, Branch $branch): bool
    {
        return $user->can('organization.branch.view');
    }

    public function create(User $user): bool
    {
        return $user->can('organization.branch.create');
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->can('organization.branch.edit');
    }

    public function deactivate(User $user, Branch $branch): bool
    {
        return $user->can('organization.branch.deactivate');
    }

    public function delete(User $user, Branch $branch): bool
    {
        return $user->can('organization.branch.delete');
    }
}