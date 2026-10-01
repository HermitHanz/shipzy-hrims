<?php

namespace App\Actions\Employees;

use App\Models\Employee;
use App\Models\EmployeeBankAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SubmitBankAccount extends EmployeeAction
{
    /**
     * Submitting an account never changes where payroll pays. It creates a PENDING record that
     * someone else must verify (ReviewBankAccount). Until then the previously verified account,
     * if any, stays in use. This blocks payroll-diversion fraud.
     *
     * @param  array{bank_name: string, account_name: string, account_number: string}  $data
     */
    public function handle(User $actor, Employee $employee, array $data): EmployeeBankAccount
    {
        $this->authorize($actor, 'updateBank', $employee);

        $data = $this->clean($data);

        foreach (['bank_name', 'account_name', 'account_number'] as $field) {
            if (empty($data[$field])) {
                $this->reject($field, 'This field is required.');
            }
        }

        $digits = preg_replace('/\D+/', '', $data['account_number']) ?? '';

        if (strlen($digits) < 8 || strlen($digits) > 20) {
            $this->reject('account_number', 'Enter a valid account number.');
        }

        return DB::transaction(function () use ($actor, $employee, $data, $digits) {
            $employee->bankAccounts()
                ->where('status', EmployeeBankAccount::PENDING)
                ->update(['status' => EmployeeBankAccount::SUPERSEDED]);

            $account = new EmployeeBankAccount();
            $account->forceFill([
                'employee_id' => $employee->id,
                'bank_name' => $data['bank_name'],
                'account_name' => $data['account_name'],
                'account_number' => $digits,
                'account_last4' => substr($digits, -4),
                'status' => EmployeeBankAccount::PENDING,
                'submitted_by' => $actor->id,
            ])->save();

            $this->audit->log('employee.bank_account_submitted', $employee, null, [
                'bank_name' => $data['bank_name'],
                'last4' => $account->account_last4,
            ], $actor);

            return $account;
        });
    }
}