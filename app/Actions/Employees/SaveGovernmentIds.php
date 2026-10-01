<?php

namespace App\Actions\Employees;

use App\Models\Employee;
use App\Models\EmployeeGovernmentId;
use App\Models\User;

class SaveGovernmentIds extends EmployeeAction
{
    /**
     * Keys: sss, tin, philhealth, pagibig. A key that is absent is left alone; a key that is
     * present but blank clears that ID. Numbers are normalized to digits, length-checked, stored
     * encrypted, and checked for duplicates through a keyed hash.
     *
     * @param  array<string, string|null>  $data
     */
    public function handle(User $actor, Employee $employee, array $data): EmployeeGovernmentId
    {
        $this->authorize($actor, 'updateGovernmentIds', $employee);

        $record = $employee->governmentId ?? new EmployeeGovernmentId(['employee_id' => $employee->id]);
        $changed = [];

        foreach (EmployeeGovernmentId::TYPES as $type) {
            if (! array_key_exists($type, $data)) {
                continue;
            }

            $raw = is_string($data[$type]) ? trim($data[$type]) : '';
            $digits = $raw === '' ? null : EmployeeGovernmentId::normalize($raw);

            if ($digits === null) {
                if (! $record->isSet($type)) {
                    continue;
                }
            } else {
                $label = EmployeeGovernmentId::LABELS[$type];

                if (! in_array(strlen($digits), EmployeeGovernmentId::LENGTHS[$type], true)) {
                    $this->reject($type, "Enter a valid {$label} number.");
                }

                $hash = EmployeeGovernmentId::hashFor($type, $digits);

                if ($record->{"{$type}_hash"} === $hash) {
                    continue;
                }

                $taken = EmployeeGovernmentId::where("{$type}_hash", $hash)
                    ->where('employee_id', '!=', $employee->id)
                    ->exists();

                if ($taken) {
                    $this->reject($type, 'This number is already in use. Please contact HR.');
                }
            }

            $record->setNumber($type, $digits);
            $changed[] = $type;
        }

        if ($changed === []) {
            return $record;
        }

        $record->save();

        $this->audit->log('employee.government_ids_updated', $employee, null, ['fields' => $changed], $actor);

        return $record;
    }
}