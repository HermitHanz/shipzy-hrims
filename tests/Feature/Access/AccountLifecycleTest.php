<?php

namespace Tests\Feature\Access;

use App\Actions\Access\CreateUserAccount;
use App\Actions\Access\SetUserStatus;
use App\Actions\Employees\ChangeEmployeeStatus;
use App\Actions\Employees\CreateEmployeeLogin;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Employment status and account access: separating an employee shuts their login,
 * admins can suspend or deactivate any account, and neither can be undone by accident.
 */
class AccountLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private int $counter = 0;

    private ?Branch $branch = null;

    private ?Department $department = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->branch = Branch::create(['code' => 'MAIN', 'name' => 'Main Office']);
        $this->department = Department::create(['code' => 'HR', 'name' => 'Human Resources']);
    }

    private function employee(): Employee
    {
        $this->counter++;

        return Employee::create([
            'employee_number' => "E-{$this->counter}",
            'first_name' => "First{$this->counter}",
            'last_name' => "Last{$this->counter}",
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'job_title' => 'Staff',
            'hire_date' => now()->toDateString(),
        ]);
    }

    private function userFor(string $role, ?Employee $employee = null): User
    {
        $this->counter++;

        $user = new User();
        $user->forceFill([
            'name' => "User {$this->counter}",
            'email' => "user{$this->counter}@test.local",
            'password' => Hash::make('password1234'),
            'status' => 'active',
            'employee_id' => $employee?->id,
        ])->save();

        $user->assignRole($role);

        return $user;
    }

    private function separation(): array
    {
        return ['separation_date' => now()->toDateString(), 'separation_reason' => 'Resigned'];
    }

    private function assertCannotUse(User $user): void
    {
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password1234'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        // An already-open session is ended on its next request.
        $this->actingAs($user->fresh())->get(route('dashboard'))->assertRedirect(route('login'));
    }

    // ---- case 1: separated employee ------------------------------------------

    public function test_separating_an_employee_locks_their_account(): void
    {
        $hr = $this->userFor('hr-admin', $this->employee());
        $employee = $this->employee();
        $login = $this->userFor('employee', $employee);

        app(ChangeEmployeeStatus::class)->handle($hr, $employee, 'separated', $this->separation());

        $this->assertSame('inactive', $login->fresh()->status);
        $this->assertCannotUse($login);
    }

    public function test_a_separated_employee_is_blocked_even_if_their_login_was_left_active(): void
    {
        $employee = $this->employee();
        $login = $this->userFor('employee', $employee);

        // Changed directly, bypassing the action on purpose.
        $employee->forceFill(['status' => 'separated'])->save();

        $this->assertSame('active', $login->fresh()->status);
        $this->assertCannotUse($login);
    }

    public function test_an_employee_without_a_login_can_be_separated_and_never_gets_one_afterwards(): void
    {
        $hr = $this->userFor('hr-admin', $this->employee());
        $employee = $this->employee();

        app(ChangeEmployeeStatus::class)->handle($hr, $employee, 'separated', $this->separation());

        $this->assertSame('separated', $employee->fresh()->status);

        try {
            app(CreateEmployeeLogin::class)->handle($hr, $employee->fresh(), 'late@test.local');
            $this->fail('A separated employee must not get a login through the employee page.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        $this->expectException(ValidationException::class);

        // ...nor through the Users screen.
        app(CreateUserAccount::class)->handle($hr, [
            'name' => 'Late Login',
            'email' => 'late2@test.local',
            'employee_id' => $employee->id,
            'roles' => ['employee'],
        ]);
    }

    public function test_a_login_cannot_be_reenabled_until_the_employee_is_reactivated(): void
    {
        $hr = $this->userFor('hr-admin', $this->employee());
        $employee = $this->employee();
        $login = $this->userFor('employee', $employee);

        app(ChangeEmployeeStatus::class)->handle($hr, $employee, 'separated', $this->separation());

        try {
            app(SetUserStatus::class)->handle($hr, $login->fresh(), 'active');
            $this->fail('The login must stay locked while the employee is separated.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }

        // Reactivating the employee does not re-enable the login by itself...
        app(ChangeEmployeeStatus::class)->handle($hr, $employee->fresh(), 'active');
        $this->assertSame('inactive', $login->fresh()->status);

        // ...an admin does that explicitly.
        app(SetUserStatus::class)->handle($hr, $login->fresh(), 'active');
        $this->assertSame('active', $login->fresh()->status);
    }

    // ---- case 2: manual suspend and deactivate ---------------------------------

    public function test_admins_can_suspend_or_deactivate_any_account_and_it_stops_working(): void
    {
        $hr = $this->userFor('hr-admin', $this->employee());

        foreach (['suspended', 'inactive'] as $status) {
            $target = $this->userFor('employee', $this->employee());

            app(SetUserStatus::class)->handle($hr, $target, $status);

            $this->assertSame($status, $target->fresh()->status);
            $this->assertCannotUse($target);
        }
    }

    public function test_an_account_with_no_employee_link_can_also_be_deactivated(): void
    {
        $hr = $this->userFor('hr-admin', $this->employee());
        $target = $this->userFor('employee');   // no employee record

        app(SetUserStatus::class)->handle($hr, $target, 'inactive');

        $this->assertCannotUse($target);
    }

    public function test_a_reactivated_account_can_sign_in_again(): void
    {
        $hr = $this->userFor('hr-admin', $this->employee());
        $target = $this->userFor('employee', $this->employee());

        app(SetUserStatus::class)->handle($hr, $target, 'suspended');
        app(SetUserStatus::class)->handle($hr, $target->fresh(), 'active');

        $this->post(route('login.store'), ['email' => $target->email, 'password' => 'password1234']);

        $this->assertAuthenticated();
    }

    public function test_access_columns_are_not_mass_assignable_and_are_never_silently_dropped(): void
    {
        $this->expectException(MassAssignmentException::class);

        User::create([
            'name' => 'Someone',
            'email' => 'someone@test.local',
            'password' => 'password1234',
            'status' => 'suspended',
        ]);
    }
}