<?php

namespace App\Actions\Employees;

use App\Actions\Access\CreateUserAccount;
use App\Models\Employee;
use App\Models\User;

class CreateEmployeeLogin extends EmployeeAction
{
    /**
     * HR's step 3: give an existing employee a login. Delegates to CreateUserAccount, so the role
     * level rules, temporary password, forced password change and audit entry all apply.
     *
     * @param  array<int, string>  $roles
     * @return array{0: User, 1: string}  the new user and the one-time temporary password
     */
    public function handle(User $actor, Employee $employee, ?string $email = null, array $roles = ['employee']): array
    {
        if ($employee->status === 'separated') {
            $this->reject('employee', 'A separated employee cannot be given a login.');
        }

        if ($employee->user()->exists()) {
            $this->reject('employee', 'This employee already has a login.');
        }

        $email = ($email !== null && trim($email) !== '') ? trim($email) : $employee->work_email;

        if (! $email) {
            $this->reject('email', 'Enter a login email, or set a work email on the employee first.');
        }

        return app(CreateUserAccount::class)->handle($actor, [
            'name' => $employee->full_name,
            'email' => $email,
            'employee_id' => $employee->id,
            'roles' => $roles,
        ]);
    }
}