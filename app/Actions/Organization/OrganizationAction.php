<?php

namespace App\Actions\Organization;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Support\Audit\AuditLogger;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Shared rules for branches and departments.
 *  - Policy denials throw AuthorizationException (403); bad input throws ValidationException keyed by field.
 *  - Units are deactivated, not deleted, once they have been used. Deleting is only for mistakes.
 */
abstract class OrganizationAction
{
    public const CODE_PATTERN = '/^[A-Z0-9][A-Z0-9-]{1,19}$/';

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

    protected function normalizeCode(?string $code): string
    {
        $code = strtoupper(trim((string) $code));

        if (! preg_match(self::CODE_PATTERN, $code)) {
            $this->reject('code', 'Use 2 to 20 letters, numbers or hyphens.');
        }

        return $code;
    }

    protected function assertName(?string $name): string
    {
        if ($name === null || $name === '') {
            $this->reject('name', 'This field is required.');
        }

        if (mb_strlen($name) > 150) {
            $this->reject('name', 'Keep the name under 150 characters.');
        }

        return $name;
    }

    /** The parent must be an active department and must not be the department itself or one of its descendants. */
    protected function assertParent(?int $parentId, ?Department $self = null): void
    {
        if ($parentId === null) {
            return;
        }

        $parent = Department::find($parentId);

        if (! $parent || ! $parent->is_active) {
            $this->reject('parent_id', 'Select an active parent department.');
        }

        if ($self) {
            $cursor = $parent;
            $seen = [];

            while ($cursor) {
                if ($cursor->id === $self->id) {
                    $this->reject('parent_id', 'A department cannot sit inside itself.');
                }

                if (isset($seen[$cursor->id])) {
                    break;
                }

                $seen[$cursor->id] = true;
                $cursor = $cursor->parent_id ? Department::find($cursor->parent_id) : null;
            }
        }
    }

    /** The head must be an employee who has not separated. */
    protected function assertHead(?int $employeeId): void
    {
        if ($employeeId === null) {
            return;
        }

        $employee = Employee::find($employeeId);

        if (! $employee || $employee->status === 'separated') {
            $this->reject('head_employee_id', 'Select an active employee.');
        }
    }

    protected function activeEmployeeCount(string $column, int $id): int
    {
        return Employee::where($column, $id)->where('status', '!=', 'separated')->count();
    }
}