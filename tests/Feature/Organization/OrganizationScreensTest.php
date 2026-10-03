<?php

namespace Tests\Feature\Organization;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * The branch and department screens: routes, permissions, and that the Actions' rules
 * (immutable codes, deactivation and deletion guards) surface as form errors.
 */
class OrganizationScreensTest extends TestCase
{
    use RefreshDatabase;

    private int $counter = 0;

    private User $hr;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->hr = $this->user('hr-admin');
    }

    private function user(?string $role = null): User
    {
        $this->counter++;

        $user = new User();
        $user->forceFill([
            'name' => "User {$this->counter}",
            'email' => "user{$this->counter}@test.local",
            'password' => Hash::make('password1234'),
            'status' => 'active',
        ])->save();

        if ($role) {
            $user->assignRole($role);
        }

        return $user;
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

    // ---- access ------------------------------------------------------------------

    public function test_hr_admin_sees_the_screens_and_the_nav_but_an_employee_does_not(): void
    {
        $branch = Branch::create(['code' => 'MAIN', 'name' => 'Main Office']);
        $department = Department::create(['code' => 'HR', 'name' => 'Human Resources']);

        $this->actingAs($this->hr)->get(route('organization.branches.index'))
            ->assertOk()->assertSee('Main Office')->assertSee(route('organization.departments.index'));
        $this->actingAs($this->hr)->get(route('organization.branches.create'))->assertOk();
        $this->actingAs($this->hr)->get(route('organization.branches.edit', $branch))->assertOk()->assertSee('MAIN');
        $this->actingAs($this->hr)->get(route('organization.departments.index'))->assertOk()->assertSee('Human Resources');
        $this->actingAs($this->hr)->get(route('organization.departments.create'))->assertOk();
        $this->actingAs($this->hr)->get(route('organization.departments.edit', $department))->assertOk();

        $employee = $this->user('employee');

        $this->actingAs($employee)->get(route('dashboard'))->assertDontSee(route('organization.branches.index'));
        $this->actingAs($employee)->get(route('organization.branches.index'))->assertForbidden();
        $this->actingAs($employee)->post(route('organization.branches.store'), ['code' => 'X1', 'name' => 'X'])->assertForbidden();
        $this->actingAs($employee)->get(route('organization.departments.edit', $department))->assertForbidden();
    }

    public function test_view_only_access_shows_a_read_only_page_without_status_controls(): void
    {
        $branch = Branch::create(['code' => 'MAIN', 'name' => 'Main Office']);
        $viewer = $this->user();
        $viewer->givePermissionTo('organization.branch.view');

        $this->actingAs($viewer)->get(route('organization.branches.edit', $branch))
            ->assertOk()
            ->assertSee('You can view this branch but not change it.')
            ->assertDontSee('Save changes')
            ->assertDontSee(route('organization.branches.status.update', $branch));

        $this->actingAs($viewer)->put(route('organization.branches.update', $branch), ['name' => 'Renamed'])->assertForbidden();
    }

    // ---- branches ----------------------------------------------------------------

    public function test_a_branch_is_created_updated_and_its_code_never_changes(): void
    {
        $this->actingAs($this->hr)->post(route('organization.branches.store'), ['code' => 'bad code!', 'name' => 'X'])
            ->assertSessionHasErrors('code');

        $response = $this->actingAs($this->hr)->post(route('organization.branches.store'), ['code' => 'mnl-01', 'name' => 'Manila', 'address' => 'Makati']);

        $branch = Branch::sole();
        $response->assertRedirect(route('organization.branches.edit', $branch));
        $this->assertSame('MNL-01', $branch->code);
        $this->assertTrue($branch->is_active);

        $this->actingAs($this->hr)->put(route('organization.branches.update', $branch), ['code' => 'OTHER', 'name' => 'Manila HQ', 'address' => null])
            ->assertSessionHasNoErrors();

        $branch->refresh();
        $this->assertSame('MNL-01', $branch->code);
        $this->assertSame('Manila HQ', $branch->name);
        $this->assertNull($branch->address);
        $this->assertSame(['branch.created', 'branch.updated'], DB::table('audit_logs')->orderBy('id')->pluck('action')->all());
    }

    public function test_a_branch_with_active_employees_cannot_be_deactivated_or_deleted(): void
    {
        $branch = Branch::create(['code' => 'MAIN', 'name' => 'Main Office']);
        $employee = $this->employee($branch, Department::create(['code' => 'HR', 'name' => 'HR']));

        $this->actingAs($this->hr)->get(route('organization.branches.edit', $branch))->assertSee('before deactivating it');

        $this->actingAs($this->hr)->patch(route('organization.branches.status.update', $branch), ['is_active' => 0])
            ->assertSessionHasErrors('is_active');
        $this->actingAs($this->hr)->delete(route('organization.branches.destroy', $branch))
            ->assertSessionHasErrors('branch');

        $employee->forceFill(['status' => 'separated'])->save();

        $this->actingAs($this->hr)->patch(route('organization.branches.status.update', $branch), ['is_active' => 0])
            ->assertSessionHasNoErrors()->assertRedirect(route('organization.branches.edit', $branch));
        $this->assertFalse($branch->fresh()->is_active);

        $this->actingAs($this->hr)->patch(route('organization.branches.status.update', $branch), ['is_active' => 1]);
        $this->assertTrue($branch->fresh()->is_active);

        // Separated staff still count as records, so deleting stays blocked
        $this->actingAs($this->hr)->delete(route('organization.branches.destroy', $branch))->assertSessionHasErrors('branch');
        $this->assertNotNull($branch->fresh());
    }

    public function test_an_unused_branch_can_be_deleted(): void
    {
        $branch = Branch::create(['code' => 'OOPS', 'name' => 'Created by mistake']);

        $this->actingAs($this->hr)->delete(route('organization.branches.destroy', $branch))
            ->assertRedirect(route('organization.branches.index'));

        $this->assertNull($branch->fresh());
        $this->assertDatabaseHas('audit_logs', ['action' => 'branch.deleted', 'target_id' => $branch->id]);
    }

    // ---- departments ---------------------------------------------------------------

    public function test_departments_are_created_with_a_parent_and_head_and_cannot_loop(): void
    {
        $branch = Branch::create(['code' => 'MAIN', 'name' => 'Main Office']);
        $parent = Department::create(['code' => 'OPS', 'name' => 'Operations']);
        $head = $this->employee($branch, $parent);

        $this->actingAs($this->hr)->post(route('organization.departments.store'), [
            'code' => 'wh', 'name' => 'Warehouse', 'parent_id' => $parent->id, 'head_employee_id' => $head->id,
        ])->assertSessionHasNoErrors();

        $child = Department::where('code', 'WH')->sole();
        $this->assertSame($parent->id, $child->parent_id);
        $this->assertSame($head->id, $child->head_employee_id);

        // Moving the parent under its own child is rejected
        $this->actingAs($this->hr)->put(route('organization.departments.update', $parent), ['name' => 'Operations', 'parent_id' => $child->id])
            ->assertSessionHasErrors('parent_id');

        // Clearing the parent and head through the form
        $this->actingAs($this->hr)->put(route('organization.departments.update', $child), ['name' => 'Warehouse', 'parent_id' => null, 'head_employee_id' => null])
            ->assertSessionHasNoErrors();
        $this->assertNull($child->fresh()->parent_id);
        $this->assertNull($child->fresh()->head_employee_id);
    }

    public function test_department_status_and_deletion_respect_sub_departments(): void
    {
        $parent = Department::create(['code' => 'OPS', 'name' => 'Operations']);
        $child = Department::create(['code' => 'WH', 'name' => 'Warehouse', 'parent_id' => $parent->id]);

        $this->actingAs($this->hr)->patch(route('organization.departments.status.update', $parent), ['is_active' => 0])
            ->assertSessionHasErrors('is_active');
        $this->actingAs($this->hr)->delete(route('organization.departments.destroy', $parent))
            ->assertSessionHasErrors('department');

        $this->actingAs($this->hr)->patch(route('organization.departments.status.update', $child), ['is_active' => 0])->assertSessionHasNoErrors();
        $this->actingAs($this->hr)->patch(route('organization.departments.status.update', $parent), ['is_active' => 0])->assertSessionHasNoErrors();

        // A child can't come back while its parent is inactive
        $this->actingAs($this->hr)->get(route('organization.departments.edit', $child))->assertSee('Reactivate the parent department');
        $this->actingAs($this->hr)->patch(route('organization.departments.status.update', $child), ['is_active' => 1])
            ->assertSessionHasErrors('is_active');

        $this->actingAs($this->hr)->delete(route('organization.departments.destroy', $child))->assertSessionHasNoErrors();
        $this->actingAs($this->hr)->delete(route('organization.departments.destroy', $parent))
            ->assertRedirect(route('organization.departments.index'));
        $this->assertSame(0, Department::count());
    }

    public function test_status_changes_are_audited(): void
    {
        $branch = Branch::create(['code' => 'MAIN', 'name' => 'Main Office']);

        $this->actingAs($this->hr)->patch(route('organization.branches.status.update', $branch), ['is_active' => 0]);

        $log = DB::table('audit_logs')->where('action', 'branch.status_changed')->sole();
        $this->assertSame($this->hr->id, (int) $log->actor_id);
        $this->assertSame(['is_active' => false], json_decode($log->new_values, true));
    }

    public function test_audit_entries_link_to_the_branch_and_department_pages(): void
    {
        foreach (['branch', 'department'] as $target) {
            $this->assertTrue(Route::has(config("hrims_audit.targets.{$target}.route")));
        }
    }
}
