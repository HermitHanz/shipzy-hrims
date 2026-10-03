<?php

namespace App\Actions\Organization;

use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SetDepartmentStatus extends OrganizationAction
{
    /**
     * Deactivating needs the department to be empty of active employees and active sub-departments.
     * Reactivating needs an active parent, so no active department ever sits under an inactive one.
     */
    public function handle(User $actor, Department $department, bool $active): Department
    {
        $this->authorize($actor, 'deactivate', $department);

        if ($department->is_active === $active) {
            return $department;
        }

        if (! $active) {
            $employees = $this->activeEmployeeCount('department_id', $department->id);

            if ($employees > 0) {
                $this->reject('is_active', "Reassign the {$employees} active employee(s) in this department first.");
            }

            $children = Department::where('parent_id', $department->id)->where('is_active', true)->count();

            if ($children > 0) {
                $this->reject('is_active', "Deactivate its {$children} active sub-department(s) first.");
            }
        } elseif ($department->parent_id) {
            $parent = Department::find($department->parent_id);

            if (! $parent || ! $parent->is_active) {
                $this->reject('is_active', 'Reactivate the parent department first.');
            }
        }

        DB::transaction(function () use ($actor, $department, $active) {
            $department->forceFill(['is_active' => $active])->save();

            $this->audit->log('department.status_changed', $department, ['is_active' => ! $active], ['is_active' => $active], $actor);
        });

        return $department;
    }
}