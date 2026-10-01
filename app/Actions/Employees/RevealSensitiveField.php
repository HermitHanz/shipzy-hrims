<?php

namespace App\Actions\Employees;

use App\Models\Employee;
use App\Models\EmployeeGovernmentId;
use App\Models\User;

class RevealSensitiveField extends EmployeeAction
{
    public const FIELDS = ['sss', 'tin', 'philhealth', 'pagibig', 'bank_account'];

    /**
     * The only sanctioned way to read a full government ID or bank account number for display.
     * Checks permission, writes an audit entry (field name only, never the value), then returns it.
     * Call it from a POST route with throttling, and never cache or log the response.
     *
     * For 'bank_account' pass the bank account id (a pending account can be revealed for verification).
     */
    public function handle(User $actor, Employee $employee, string $field, ?int $bankAccountId = null): string
    {
        if (! in_array($field, self::FIELDS, true)) {
            $this->reject('field', 'Unknown field.');
        }

        if ($field === 'bank_account') {
            $this->authorize($actor, 'revealBank', $employee);

            $account = $bankAccountId ? $employee->bankAccounts()->find($bankAccountId) : null;

            if (! $account) {
                $this->reject('field', 'Bank account not found.');
            }

            $value = $account->account_number;
        } else {
            $this->authorize($actor, 'revealGovernmentId', $employee);

            $record = $employee->governmentId;
            $value = $record?->plain($field);
        }

        if ($value === null || $value === '') {
            $this->reject('field', 'Nothing is on file for this field.');
        }

        $this->audit->log('employee.sensitive_revealed', $employee, null, ['field' => $field], $actor);

        return $value;
    }
}