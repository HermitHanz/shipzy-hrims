<?php

namespace App\Actions\Employees;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Support\Audit\AuditLogger;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Conventions (same as the access actions):
 *  - Policy denials throw AuthorizationException (403).
 *  - Bad input throws ValidationException keyed by field.
 *  - Audit entries record WHICH fields changed, never the values of personal data.
 */
abstract class EmployeeAction
{
    public const EMPLOYMENT_TYPES = ['regular', 'probationary', 'contractual', 'project_based', 'part_time', 'intern'];

    public const STATUSES = ['active', 'on_leave', 'separated'];

    public function __construct(protected AuditLogger $audit)
    {
    }

    protected function authorize(User $actor, string $ability, mixed $arguments): void
    {
        Gate::forUser($actor)->authorize($ability, $arguments);
    }

    protected function reject(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }

    /** Trim strings; empty strings become null. */
    protected function clean(array $data): array
    {
        return array_map(function ($value) {
            if (is_string($value)) {
                $value = trim($value);

                return $value === '' ? null : $value;
            }

            return $value;
        }, $data);
    }

    protected function assertBirthDate(mixed $value): void
    {
        try {
            $date = Carbon::parse($value);
        } catch (Throwable) {
            $this->reject('birth_date', 'Enter a valid date.');
        }

        if ($date->lt(Carbon::create(1900, 1, 1)) || $date->gt(now()->subYears(15))) {
            $this->reject('birth_date', 'Enter a valid birth date.');
        }
    }

    /** Branch and department must be active, the manager must be an active employee, and no reporting loops. */
    protected function assertPlacement(int $branchId, int $departmentId, ?int $managerId, ?Employee $self = null): void
    {
        if (! Branch::whereKey($branchId)->where('is_active', true)->exists()) {
            $this->reject('branch_id', 'Select an active branch.');
        }

        if (! Department::whereKey($departmentId)->where('is_active', true)->exists()) {
            $this->reject('department_id', 'Select an active department.');
        }

        if ($managerId === null) {
            return;
        }

        $manager = Employee::find($managerId);

        if (! $manager || $manager->status === 'separated') {
            $this->reject('manager_id', 'Select an active manager.');
        }

        if ($self) {
            $cursor = $manager;
            $seen = [];

            while ($cursor) {
                if ($cursor->id === $self->id) {
                    $this->reject('manager_id', 'That would create a reporting loop.');
                }

                if (isset($seen[$cursor->id])) {
                    break;
                }

                $seen[$cursor->id] = true;
                $cursor = $cursor->manager_id ? Employee::find($cursor->manager_id) : null;
            }
        }
    }
}