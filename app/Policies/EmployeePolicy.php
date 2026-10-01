<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;
use App\Support\Access\ScopeResolver;

/**
 * Core record access follows the scope resolver (own / team / department / all).
 * Protected data (personal details, emergency contacts, government IDs, bank accounts)
 * uses separate permissions: *.view-own / *.view-all to see masked data, *.edit-own / *.edit
 * to change it, and *.reveal to see full numbers (every reveal is audited).
 */
class EmployeePolicy
{
    public function __construct(private ScopeResolver $scopes)
    {
    }

    // ---- core record ------------------------------------------------------

    public function viewAny(User $user): bool
    {
        return $this->scopes->resolve($user, 'employee.record.view') !== ScopeResolver::NONE;
    }

    public function view(User $user, Employee $employee): bool
    {
        return $this->scopes->allows($user, 'employee.record.view', $employee);
    }

    public function create(User $user): bool
    {
        return $user->can('employee.record.create');
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->can('employee.record.edit');
    }

    /** Changing branch, department or manager changes who can see whom, so it has its own permission. */
    public function reassign(User $user, Employee $employee): bool
    {
        return $user->can('employee.record.reassign');
    }

    public function changeStatus(User $user, Employee $employee): bool
    {
        return $user->can('employee.record.edit');
    }

    // ---- personal details -------------------------------------------------

    public function viewPersonal(User $user, Employee $employee): bool
    {
        return $this->ownOrAll($user, $employee, 'employee.personal.view-own', 'employee.personal.view-all');
    }

    public function updatePersonal(User $user, Employee $employee): bool
    {
        return $this->ownOrAll($user, $employee, 'employee.personal.edit-own', 'employee.personal.edit');
    }

    // ---- emergency contacts ----------------------------------------------

    public function viewEmergency(User $user, Employee $employee): bool
    {
        return $this->ownOrAll($user, $employee, 'employee.emergency.view-own', 'employee.emergency.view-all');
    }

    public function updateEmergency(User $user, Employee $employee): bool
    {
        return $this->ownOrAll($user, $employee, 'employee.emergency.edit-own', 'employee.emergency.edit');
    }

    // ---- government IDs ---------------------------------------------------

    public function viewGovernmentIds(User $user, Employee $employee): bool
    {
        return $this->ownOrAll($user, $employee, 'employee.government-id.view-own', 'employee.government-id.view-all');
    }

    public function updateGovernmentIds(User $user, Employee $employee): bool
    {
        return $this->ownOrAll($user, $employee, 'employee.government-id.edit-own', 'employee.government-id.edit');
    }

    public function revealGovernmentId(User $user, Employee $employee): bool
    {
        return $this->ownOrAll($user, $employee, 'employee.government-id.view-own', 'employee.government-id.reveal');
    }

    // ---- bank accounts ----------------------------------------------------

    public function viewBank(User $user, Employee $employee): bool
    {
        return $this->ownOrAll($user, $employee, 'employee.bank.view-own', 'employee.bank.view-all');
    }

    public function updateBank(User $user, Employee $employee): bool
    {
        return $this->ownOrAll($user, $employee, 'employee.bank.edit-own', 'employee.bank.edit');
    }

    /** Nobody verifies their own bank account. */
    public function verifyBank(User $user, Employee $employee): bool
    {
        return $user->can('employee.bank.verify') && ! $this->isSelf($user, $employee);
    }

    public function revealBank(User $user, Employee $employee): bool
    {
        return $this->ownOrAll($user, $employee, 'employee.bank.view-own', 'employee.bank.reveal');
    }

    // ---- helpers ----------------------------------------------------------

    private function isSelf(User $user, Employee $employee): bool
    {
        return $user->employee_id !== null && $user->employee_id === $employee->id;
    }

    private function ownOrAll(User $user, Employee $employee, string $ownKey, string $allKey): bool
    {
        return $user->can($allKey) || ($this->isSelf($user, $employee) && $user->can($ownKey));
    }
}