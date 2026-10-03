<?php

namespace App\Actions\Organization;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeleteDepartment extends OrganizationAction
{
    /** Only for departments created by mistake. Anything that has been used must be deactivated instead. */
    public function handle(User $actor, Department $department): void
    {
        $this->authorize($actor, 'delete', $department);

        if (Employee::withTrashed()->where('department_id', $department->id)->exists()) {
            $this->reject('department', 'This department has employee records. Deactivate it instead.');
        }

        if (Department::where('parent_id', $department->id)->exists()) {
            $this->reject('department', 'Remove or move its sub-departments first.');
        }

        DB::transaction(function () use ($actor, $department) {
            $this->audit->log('department.deleted', $department, ['code' => $department->code, 'name' => $department->name], null, $actor);

            $department->delete();
        });
    }
}