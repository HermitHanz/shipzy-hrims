<?php

namespace App\Actions\Employees;

use App\Actions\Access\SetUserStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ChangeEmployeeStatus extends EmployeeAction
{
    /**
     * Separating someone also deactivates their login (in the same transaction, so if the actor
     * may not deactivate accounts, nothing changes). Reactivating an employee does NOT re-enable
     * the login; do that explicitly from the Users screen.
     *
     * @param  array{separation_date?: string|null, separation_reason?: string|null}  $details
     */
    public function handle(User $actor, Employee $employee, string $status, array $details = []): Employee
    {
        $this->authorize($actor, 'changeStatus', $employee);

        if (! in_array($status, self::STATUSES, true)) {
            $this->reject('status', 'Invalid status.');
        }

        if ($employee->status === $status) {
            return $employee;
        }

        $details = $this->clean($details);

        if ($status === 'separated') {
            if (empty($details['separation_date'])) {
                $this->reject('separation_date', 'Enter the separation date.');
            }

            if (empty($details['separation_reason'])) {
                $this->reject('separation_reason', 'Enter the reason for separation.');
            }

            $reports = $employee->directReports()->where('status', '!=', 'separated')->count();

            if ($reports > 0) {
                $this->reject('status', "Reassign this person's {$reports} direct report(s) first.");
            }

            if (Department::where('head_employee_id', $employee->id)->exists()) {
                $this->reject('status', 'Replace this person as department head first.');
            }
        }

        DB::transaction(function () use ($actor, $employee, $status, $details) {
            $old = $employee->status;

            $employee->forceFill([
                'status' => $status,
                'separation_date' => $status === 'separated' ? $details['separation_date'] : null,
                'separation_reason' => $status === 'separated' ? $details['separation_reason'] : null,
            ])->save();

            if ($status === 'separated') {
                $login = $employee->user;

                if ($login && $login->status === 'active') {
                    app(SetUserStatus::class)->handle($actor, $login, 'inactive');
                }
            }

            $this->audit->log('employee.status_changed', $employee, ['status' => $old], ['status' => $status], $actor);
        });

        return $employee->refresh();
    }
}