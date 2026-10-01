<?php

namespace App\Actions\Employees;

use App\Models\Employee;
use App\Models\EmployeePersonalDetail;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class UpdatePersonalDetails extends EmployeeAction
{
    /**
     * Personal email, birthday, residential address, and the mobile number (which lives on the
     * employee record but is treated as a personal contact). Used by HR and by the employee.
     */
    public function handle(User $actor, Employee $employee, array $data): EmployeePersonalDetail
    {
        $this->authorize($actor, 'updatePersonal', $employee);

        $data = $this->clean($data);
        $details = Arr::only($data, EmployeePersonalDetail::FIELDS);

        if (! empty($details['birth_date'])) {
            $this->assertBirthDate($details['birth_date']);
        }

        if (! empty($details['personal_email']) && ! filter_var($details['personal_email'], FILTER_VALIDATE_EMAIL)) {
            $this->reject('personal_email', 'Enter a valid email address.');
        }

        $record = $employee->personalDetail ?? new EmployeePersonalDetail(['employee_id' => $employee->id]);
        $record->fill($details);

        $fields = array_values(array_diff(array_keys($record->getDirty()), ['employee_id']));

        $phoneChanged = array_key_exists('phone', $data) && ($data['phone'] ?? null) !== $employee->phone;

        if ($phoneChanged) {
            $fields[] = 'phone';
        }

        if ($fields === []) {
            return $record;
        }

        DB::transaction(function () use ($actor, $employee, $record, $data, $phoneChanged, $fields) {
            $record->save();

            if ($phoneChanged) {
                $employee->forceFill(['phone' => $data['phone']])->save();
            }

            // Field names only. The values are personal data and stay out of the audit log.
            $this->audit->log('employee.personal_updated', $employee, null, ['fields' => $fields], $actor);
        });

        return $record;
    }
}