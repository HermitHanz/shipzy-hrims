<?php

namespace App\Actions\Employees;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdateEmployee extends EmployeeAction
{
    private const EDITABLE = [
        'first_name', 'middle_name', 'last_name', 'work_email', 'phone',
        'branch_id', 'department_id', 'manager_id', 'job_title', 'employment_type',
        'hire_date', 'regularization_date',
    ];

    private const PLACEMENT = ['branch_id', 'department_id', 'manager_id'];

    /** Core record only (employee number never changes). Placement changes need the reassign permission. */
    public function handle(User $actor, Employee $employee, array $data): Employee
    {
        $this->authorize($actor, 'update', $employee);

        $data = Arr::only($this->clean($data), self::EDITABLE);

        $placement = [];

        foreach (self::PLACEMENT as $field) {
            if (array_key_exists($field, $data) && (int) ($data[$field] ?? 0) !== (int) ($employee->{$field} ?? 0)) {
                $placement[$field] = [$employee->{$field}, $data[$field] ?? null];
            }
        }

        if ($placement !== []) {
            $this->authorize($actor, 'reassign', $employee);

            $manager = array_key_exists('manager_id', $data) ? $data['manager_id'] : $employee->manager_id;

            $this->assertPlacement(
                (int) ($data['branch_id'] ?? $employee->branch_id),
                (int) ($data['department_id'] ?? $employee->department_id),
                $manager === null ? null : (int) $manager,
                $employee,
            );
        }

        if (isset($data['employment_type']) && ! in_array($data['employment_type'], self::EMPLOYMENT_TYPES, true)) {
            $this->reject('employment_type', 'Select a valid employment type.');
        }

        if (! empty($data['work_email'])
            && Employee::withTrashed()->where('work_email', $data['work_email'])->where('id', '!=', $employee->id)->exists()) {
            $this->reject('work_email', 'That work email is already in use.');
        }

        $employee->fill($data);
        $changed = array_keys($employee->getDirty());

        if ($changed === []) {
            return $employee;
        }

        DB::transaction(function () use ($actor, $employee, $changed, $placement) {
            $employee->save();

            $this->audit->log(
                'employee.updated',
                $employee,
                $placement !== [] ? ['placement' => array_map(fn ($pair) => $pair[0], $placement)] : null,
                ['fields' => $changed, 'placement' => array_map(fn ($pair) => $pair[1], $placement)],
                $actor,
            );
        });

        return $employee->refresh();
    }
}