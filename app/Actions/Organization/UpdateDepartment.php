<?php

namespace App\Actions\Organization;

use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateDepartment extends OrganizationAction
{
    /**
     * The code never changes. Parent and head are only validated when they actually change, so
     * renaming a department whose parent has since been deactivated still works.
     */
    public function handle(User $actor, Department $department, array $data): Department
    {
        $this->authorize($actor, 'update', $department);

        $data = $this->clean($data);

        if (isset($data['code']) && strtoupper($data['code']) !== $department->code) {
            $this->reject('code', 'The code cannot be changed.');
        }

        $name = $this->assertName($data['name'] ?? null);

        $parentId = array_key_exists('parent_id', $data)
            ? ($data['parent_id'] === null ? null : (int) $data['parent_id'])
            : $department->parent_id;

        $headId = array_key_exists('head_employee_id', $data)
            ? ($data['head_employee_id'] === null ? null : (int) $data['head_employee_id'])
            : $department->head_employee_id;

        if ((int) $parentId !== (int) $department->parent_id) {
            $this->assertParent($parentId, $department);
        }

        if ((int) $headId !== (int) $department->head_employee_id) {
            $this->assertHead($headId);
        }

        $old = [
            'name' => $department->name,
            'parent_id' => $department->parent_id,
            'head_employee_id' => $department->head_employee_id,
        ];

        $department->forceFill(['name' => $name, 'parent_id' => $parentId, 'head_employee_id' => $headId]);

        if (! $department->isDirty()) {
            return $department;
        }

        DB::transaction(function () use ($actor, $department, $old, $name, $parentId, $headId) {
            $department->save();

            $this->audit->log('department.updated', $department, $old, [
                'name' => $name,
                'parent_id' => $parentId,
                'head_employee_id' => $headId,
            ], $actor);
        });

        return $department;
    }
}