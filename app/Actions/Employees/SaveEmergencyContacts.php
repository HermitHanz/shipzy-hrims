<?php

namespace App\Actions\Employees;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SaveEmergencyContacts extends EmployeeAction
{
    private const MAX_CONTACTS = 3;

    /**
     * Replaces the employee's emergency contacts with the given list (an empty list clears them).
     *
     * @param  array<int, array{name: string, relationship: string, phone: string, alt_phone?: string|null, address?: string|null, is_primary?: bool}>  $contacts
     */
    public function handle(User $actor, Employee $employee, array $contacts): Employee
    {
        $this->authorize($actor, 'updateEmergency', $employee);

        $contacts = array_values(array_map(fn ($contact) => $this->clean($contact), $contacts));

        if (count($contacts) > self::MAX_CONTACTS) {
            $this->reject('contacts', 'You can list up to ' . self::MAX_CONTACTS . ' emergency contacts.');
        }

        foreach ($contacts as $contact) {
            foreach (['name', 'relationship', 'phone'] as $field) {
                if (empty($contact[$field])) {
                    $this->reject('contacts', 'Each contact needs a name, relationship and phone number.');
                }
            }
        }

        if ($contacts !== []) {
            $primaries = count(array_filter($contacts, fn ($contact) => ! empty($contact['is_primary'])));

            if ($primaries > 1) {
                $this->reject('contacts', 'Choose only one primary contact.');
            }

            if ($primaries === 0) {
                $contacts[0]['is_primary'] = true;
            }
        }

        DB::transaction(function () use ($actor, $employee, $contacts) {
            $employee->emergencyContacts()->delete();

            foreach ($contacts as $contact) {
                $employee->emergencyContacts()->create(
                    Arr::only($contact, ['name', 'relationship', 'phone', 'alt_phone', 'address', 'is_primary'])
                );
            }

            $this->audit->log('employee.emergency_contacts_updated', $employee, null, ['count' => count($contacts)], $actor);
        });

        return $employee->refresh();
    }
}