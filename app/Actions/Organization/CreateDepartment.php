<?php

namespace App\Actions\Organization;

use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateDepartment extends OrganizationAction
{
    /** @param  array{code: string, name: string, parent_id?: int|null, head_employee_id?: int|null}  $data */
    public function handle(User $actor, array $data): Department
    {
        $this->authorize($actor, 'create', Department::class);

        $data = $this->clean($data);
        $code = $this->normalizeCode($data['code'] ?? null);
        $name = $this->assertName($data['name'] ?? null);
        $parentId = isset($data['parent_id']) ? (int) $data['parent_id'] : null;
        $headId = isset($data['head_employee_id']) ? (int) $data['head_employee_id'] : null;

        if (Department::where('code', $code)->exists()) {
            $this->reject('code', 'That code is already in use.');
        }

        $this->assertParent($parentId);
        $this->assertHead($headId);

        return DB::transaction(function () use ($actor, $code, $name, $parentId, $headId) {
            $department = Department::create([
                'code' => $code,
                'name' => $name,
                'parent_id' => $parentId,
                'head_employee_id' => $headId,
                'is_active' => true,
            ]);

            $this->audit->log('department.created', $department, null, [
                'code' => $code,
                'name' => $name,
                'parent_id' => $parentId,
                'head_employee_id' => $headId,
            ], $actor);

            return $department;
        });
    }
}