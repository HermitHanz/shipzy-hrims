<?php

namespace App\Actions\Employees;

use App\Models\Employee;
use App\Models\EmployeePersonalDetail;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CreateEmployee extends EmployeeAction
{
    private const CORE = [
        'employee_number', 'first_name', 'middle_name', 'last_name', 'work_email', 'phone',
        'branch_id', 'department_id', 'manager_id', 'job_title', 'employment_type',
        'hire_date', 'regularization_date',
    ];

    /**
     * HR's first step: the basics (name, birthday, address, mobile number, placement).
     * Personal fields are optional here; the employee confirms and completes them at onboarding.
     */
    public function handle(User $actor, array $data): Employee
    {
        $this->authorize($actor, 'create', Employee::class);

        $data = $this->clean($data);
        $core = Arr::only($data, self::CORE);
        $personal = array_filter(Arr::only($data, EmployeePersonalDetail::FIELDS), fn ($value) => $value !== null);

        if ($personal !== [] && ! $actor->can('employee.personal.edit')) {
            $this->reject('personal', 'You do not have permission to record personal details.');
        }

        foreach (['employee_number', 'first_name', 'last_name', 'branch_id', 'department_id', 'job_title', 'hire_date'] as $field) {
            if (empty($core[$field])) {
                $this->reject($field, 'This field is required.');
            }
        }

        $core['employment_type'] ??= 'regular';

        if (! in_array($core['employment_type'], self::EMPLOYMENT_TYPES, true)) {
            $this->reject('employment_type', 'Select a valid employment type.');
        }

        if (isset($personal['birth_date'])) {
            $this->assertBirthDate($personal['birth_date']);
        }

        if (Employee::withTrashed()->where('employee_number', $core['employee_number'])->exists()) {
            $this->reject('employee_number', 'That employee number is already in use.');
        }

        if (! empty($core['work_email']) && Employee::withTrashed()->where('work_email', $core['work_email'])->exists()) {
            $this->reject('work_email', 'That work email is already in use.');
        }

        $managerId = isset($core['manager_id']) ? (int) $core['manager_id'] : null;
        $this->assertPlacement((int) $core['branch_id'], (int) $core['department_id'], $managerId);

        return DB::transaction(function () use ($actor, $core, $personal) {
            $employee = Employee::create($core);

            if ($personal !== []) {
                $employee->personalDetail()->create($personal);
            }

            $this->audit->log('employee.created', $employee, null, [
                ...Arr::only($core, ['employee_number', 'branch_id', 'department_id', 'manager_id', 'job_title', 'employment_type', 'hire_date']),
                'personal_fields' => array_keys($personal),
            ], $actor);

            return $employee->refresh();
        });
    }
}