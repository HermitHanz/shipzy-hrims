<?php

namespace App\Support\Employees;

use App\Models\Employee;
use App\Models\User;

class EmployeeSections
{
    public static function data(Employee $employee, User $user): array
    {
        $employee->loadMissing(['branch', 'department', 'manager', 'user']);

        if ($user->can('viewPersonal', $employee))       $employee->loadMissing('personalDetail');
        if ($user->can('viewEmergency', $employee))      $employee->loadMissing('emergencyContacts');
        if ($user->can('viewGovernmentIds', $employee))  $employee->loadMissing('governmentId');

        $bankAccounts = collect();
        if ($user->can('viewBank', $employee)) {
            $employee->loadMissing('currentBankAccount');
            $bankAccounts = $employee->bankAccounts()->with(['submitter', 'reviewer'])->latest()->get();
        }

        return ['bankAccounts' => $bankAccounts];
    }
}