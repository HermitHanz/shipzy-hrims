<?php

namespace App\Actions\Employees;

use App\Models\Employee;
use App\Models\EmployeePersonalDetail;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CompleteOnboarding extends EmployeeAction
{
    /** Everything the employee must have on file before using the system. */
    public const REQUIRED = ['personal_email', 'birth_date', 'address_line1', 'city', 'province', 'postal_code', 'phone'];

    /**
     * The employee's own first-login step. HR may already have entered some values; the employee
     * confirms them and fills the rest. Government IDs, emergency contacts and bank details are NOT
     * required here; they can follow later through their own actions.
     */
    public function handle(User $actor, Employee $employee, array $data): Employee
    {
        if ($actor->employee_id === null || $actor->employee_id !== $employee->id) {
            throw new AuthorizationException('You can only complete your own onboarding.');
        }

        $this->authorize($actor, 'updatePersonal', $employee);

        if ($employee->onboarding_completed_at !== null) {
            return $employee;
        }

        $data = $this->clean($data);

        $current = ['phone' => $employee->phone] + ($employee->personalDetail?->only(EmployeePersonalDetail::FIELDS) ?? []);
        $incoming = Arr::only($data, [...EmployeePersonalDetail::FIELDS, 'phone']);
        $effective = array_merge($current, array_filter($incoming, fn ($value) => $value !== null));

        foreach (self::REQUIRED as $field) {
            if (empty($effective[$field])) {
                $this->reject($field, 'This field is required.');
            }
        }

        if (! filter_var($data['accept_privacy'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $this->reject('accept_privacy', 'Please acknowledge the data privacy notice to continue.');
        }

        DB::transaction(function () use ($actor, $employee, $incoming) {
            app(UpdatePersonalDetails::class)->handle($actor, $employee, $incoming);

            $employee->forceFill([
                'onboarding_completed_at' => now(),
                'privacy_acknowledged_at' => now(),
            ])->save();

            $this->audit->log('employee.onboarding_completed', $employee, null, null, $actor);
        });

        return $employee->refresh();
    }
}