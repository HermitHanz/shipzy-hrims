<?php

namespace Tests\Feature\Organization;

use App\Actions\Employees\CreateEmployee;
use App\Actions\Organization\CreateBranch;
use App\Actions\Organization\CreateDepartment;
use App\Actions\Organization\DeleteBranch;
use App\Actions\Organization\DeleteDepartment;
use App\Actions\Organization\SetBranchStatus;
use App\Actions\Organization\SetDepartmentStatus;
use App\Actions\Organization\UpdateBranch;
use App\Actions\Organization\UpdateDepartment;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    private int $counter = 0;

    private User $hr;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->hr = $this->userWithRole('hr-admin');
    }

    private function userWithRole(string $role): User
    {
        $this->counter++;

        $user = new User();
        $user->forceFill([
            'name' => "User {$this->counter}",
            'email' => "user{$this->counter}@test.local",
            'password' => Hash::make('password1234'),
            'status' => 'active',
        ])->save();

        $user->assignRole($role);

        return $user;
    }

    private function branch(?string $code = null): Branch
    {
        $this->counter++;

        return app(CreateBranch::class)->handle($this->hr, ['code' => $code ?? "BR-{$this->counter}", 'name' => "Branch {$this->counter}"]);
    }

    private function department(array $extra = []): Department
    {
        $this->counter++;

        return app(CreateDepartment::class)->handle($this->hr, array_merge(['code' => "DP-{$this->counter}", 'name' => "Dept {$this->counter}"], $extra));
    }

    private function employee(Branch $branch, Department $department): Employee
    {
        $this->counter++;

        return Employee::create([
            'employee_number' => "E-{$this->counter}",
            'first_name' => "First{$this->counter}",
            'last_name' => "Last{$this->counter}",
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'job_title' => 'Staff',
            'hire_date' => now()->toDateString(),
        ]);
    }

    private function assertRejects(callable $action, string $field): void
    {
        try {
            $action();
            $this->fail("Expected a validation error on '{$field}'.");
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($field, $e->errors());
        }
    }

    // ---- branches --------------------------------------------------------------

    public function test_hr_admin_can_manage_branches_but_an_employee_cannot(): void
    {
        $branch = $this->branch('makati');

        $this->assertSame('MAKATI', $branch->code);
        $this->assertTrue($branch->is_active);

        $this->expectException(AuthorizationException::class);

        app(CreateBranch::class)->handle($this->userWithRole('employee'), ['code' => 'NOPE', 'name' => 'Nope']);
    }

    public function test_branch_codes_must_be_valid_unique_and_never_change(): void
    {
        $branch = $this->branch('main');

        $this->assertRejects(fn () => $this->branch('MAIN'), 'code');                 // duplicate (case-insensitive)
        $this->assertRejects(fn () => $this->branch('x'), 'code');                    // too short
        $this->assertRejects(fn () => $this->branch('bad code!'), 'code');            // invalid characters
        $this->assertRejects(fn () => app(UpdateBranch::class)->handle($this->hr, $branch, ['code' => 'OTHER', 'name' => 'Main']), 'code');

        app(UpdateBranch::class)->handle($this->hr, $branch, ['name' => 'Main Office', 'address' => '1 Main Street']);

        $this->assertSame('Main Office', $branch->fresh()->name);
        $this->assertSame('MAIN', $branch->fresh()->code);
    }

    public function test_a_branch_with_active_employees_cannot_be_deactivated(): void
    {
        $branch = $this->branch();
        $employee = $this->employee($branch, $this->department());

        $this->assertRejects(fn () => app(SetBranchStatus::class)->handle($this->hr, $branch, false), 'is_active');

        // Once everyone has separated, it can be closed.
        $employee->forceFill(['status' => 'separated'])->save();

        app(SetBranchStatus::class)->handle($this->hr, $branch, false);
        $this->assertFalse($branch->fresh()->is_active);

        // An inactive branch can't receive new employees.
        $this->assertRejects(fn () => app(CreateEmployee::class)->handle($this->hr, [
            'employee_number' => 'E-NEW',
            'first_name' => 'New',
            'last_name' => 'Hire',
            'branch_id' => $branch->id,
            'department_id' => $this->department()->id,
            'job_title' => 'Analyst',
            'hire_date' => now()->toDateString(),
        ]), 'branch_id');

        app(SetBranchStatus::class)->handle($this->hr, $branch, true);
        $this->assertTrue($branch->fresh()->is_active);
    }

    public function test_only_unused_branches_can_be_deleted(): void
    {
        $used = $this->branch();
        $this->employee($used, $this->department());

        $this->assertRejects(fn () => app(DeleteBranch::class)->handle($this->hr, $used), 'branch');

        $unused = $this->branch();
        app(DeleteBranch::class)->handle($this->hr, $unused);

        $this->assertNull(Branch::find($unused->id));
    }

    // ---- departments -----------------------------------------------------------

    public function test_department_hierarchy_rules(): void
    {
        $top = $this->department();
        $child = $this->department(['parent_id' => $top->id]);

        // A department can't be moved under its own descendant (or itself).
        $this->assertRejects(fn () => app(UpdateDepartment::class)->handle($this->hr, $top, ['name' => $top->name, 'parent_id' => $child->id]), 'parent_id');
        $this->assertRejects(fn () => app(UpdateDepartment::class)->handle($this->hr, $top, ['name' => $top->name, 'parent_id' => $top->id]), 'parent_id');

        // An inactive department can't be a parent.
        $leaf = $this->department();
        app(SetDepartmentStatus::class)->handle($this->hr, $leaf, false);

        $this->assertRejects(fn () => $this->department(['parent_id' => $leaf->id]), 'parent_id');

        // A valid move works.
        app(UpdateDepartment::class)->handle($this->hr, $child, ['name' => 'Renamed', 'parent_id' => null]);
        $this->assertNull($child->fresh()->parent_id);
    }

    public function test_a_department_head_must_be_an_active_employee(): void
    {
        $employee = $this->employee($this->branch(), $this->department());

        $department = $this->department(['head_employee_id' => $employee->id]);
        $this->assertSame($employee->id, $department->head_employee_id);

        $this->assertRejects(fn () => $this->department(['head_employee_id' => 999999]), 'head_employee_id');

        $employee->forceFill(['status' => 'separated'])->save();

        $this->assertRejects(fn () => $this->department(['head_employee_id' => $employee->id]), 'head_employee_id');

        // Renaming a department whose current head has since left still works.
        app(UpdateDepartment::class)->handle($this->hr, $department, ['name' => 'Renamed']);
        $this->assertSame('Renamed', $department->fresh()->name);
    }

    public function test_departments_with_staff_or_active_children_cannot_be_deactivated_and_children_need_an_active_parent(): void
    {
        $parent = $this->department();
        $child = $this->department(['parent_id' => $parent->id]);

        // Active child blocks deactivating the parent.
        $this->assertRejects(fn () => app(SetDepartmentStatus::class)->handle($this->hr, $parent, false), 'is_active');

        // Active employees block deactivating the child.
        $employee = $this->employee($this->branch(), $child);
        $this->assertRejects(fn () => app(SetDepartmentStatus::class)->handle($this->hr, $child, false), 'is_active');

        $employee->forceFill(['status' => 'separated'])->save();

        app(SetDepartmentStatus::class)->handle($this->hr, $child, false);
        app(SetDepartmentStatus::class)->handle($this->hr, $parent, false);

        // Reactivating the child while its parent is inactive is refused.
        $this->assertRejects(fn () => app(SetDepartmentStatus::class)->handle($this->hr, $child, true), 'is_active');

        app(SetDepartmentStatus::class)->handle($this->hr, $parent, true);
        app(SetDepartmentStatus::class)->handle($this->hr, $child, true);

        $this->assertTrue($child->fresh()->is_active);
    }

    public function test_only_unused_departments_without_children_can_be_deleted(): void
    {
        $parent = $this->department();
        $this->department(['parent_id' => $parent->id]);

        $this->assertRejects(fn () => app(DeleteDepartment::class)->handle($this->hr, $parent), 'department');

        $used = $this->department();
        $this->employee($this->branch(), $used);
        $this->assertRejects(fn () => app(DeleteDepartment::class)->handle($this->hr, $used), 'department');

        $unused = $this->department();
        app(DeleteDepartment::class)->handle($this->hr, $unused);
        $this->assertNull(Department::find($unused->id));
    }

    // ---- audit -----------------------------------------------------------------

    public function test_changes_are_audited(): void
    {
        $branch = $this->branch();
        app(UpdateBranch::class)->handle($this->hr, $branch, ['name' => 'Updated name']);
        app(SetBranchStatus::class)->handle($this->hr, $branch, false);

        $department = $this->department();
        app(SetDepartmentStatus::class)->handle($this->hr, $department, false);
        app(DeleteDepartment::class)->handle($this->hr, $department);

        foreach (['branch.created', 'branch.updated', 'branch.status_changed', 'department.created', 'department.status_changed', 'department.deleted'] as $action) {
            $this->assertDatabaseHas('audit_logs', ['action' => $action, 'actor_id' => $this->hr->id]);
        }
    }
}