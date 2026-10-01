<?php

namespace Tests\Feature\Employees;

use App\Actions\Employees\ChangeEmployeeStatus;
use App\Actions\Employees\CompleteOnboarding;
use App\Actions\Employees\CreateEmployee;
use App\Actions\Employees\ReviewBankAccount;
use App\Actions\Employees\RevealSensitiveField;
use App\Actions\Employees\SaveGovernmentIds;
use App\Actions\Employees\SubmitBankAccount;
use App\Actions\Employees\UpdateEmployee;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class EmployeeProtectionTest extends TestCase
{
    use RefreshDatabase;

    private int $counter = 0;

    private ?Branch $branch = null;

    private ?Department $department = null;

    protected function setUp(): void
    {
        parent::setUp();

        config(['hrims.pii_hash_key' => 'test-only-hash-key']);

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->branch = Branch::create(['code' => 'MAIN', 'name' => 'Main Office']);
        $this->department = Department::create(['code' => 'HR', 'name' => 'Human Resources']);
    }

    private function employee(array $attributes = []): Employee
    {
        $this->counter++;

        return Employee::create(array_merge([
            'employee_number' => "E-{$this->counter}",
            'first_name' => "First{$this->counter}",
            'last_name' => "Last{$this->counter}",
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'job_title' => 'Staff',
            'hire_date' => now()->toDateString(),
        ], $attributes));
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

    // ---- government IDs ----------------------------------------------------

    public function test_government_ids_are_encrypted_at_rest_and_masked(): void
    {
        $hr = $this->userFor('hr-admin', $this->employee());
        $target = $this->employee();

        app(SaveGovernmentIds::class)->handle($hr, $target, ['sss' => '34-1234567-8']);

        $raw = DB::table('employee_government_ids')->where('employee_id', $target->id)->first();

        $this->assertStringNotContainsString('3412345678', json_encode($raw));
        $this->assertSame('5678', $raw->sss_last4);

        $record = $target->fresh()->governmentId;

        $this->assertSame('••••••5678', $record->masked('sss'));
        $this->assertArrayNotHasKey('sss_number', $record->toArray());
    }

    public function test_duplicate_government_ids_are_rejected(): void
    {
        $hr = $this->userFor('hr-admin', $this->employee());

        app(SaveGovernmentIds::class)->handle($hr, $this->employee(), ['sss' => '3412345678']);

        $this->expectException(ValidationException::class);

        app(SaveGovernmentIds::class)->handle($hr, $this->employee(), ['sss' => '34-1234567-8']);
    }

    public function test_an_employee_can_only_view_their_own_protected_data(): void
    {
        $mine = $this->employee();
        $theirs = $this->employee();
        $user = $this->userFor('employee', $mine);

        $this->assertTrue(Gate::forUser($user)->allows('viewGovernmentIds', $mine));
        $this->assertFalse(Gate::forUser($user)->allows('viewGovernmentIds', $theirs));
        $this->assertFalse(Gate::forUser($user)->allows('viewBank', $theirs));
        $this->assertFalse(Gate::forUser($user)->allows('viewPersonal', $theirs));
    }

    public function test_hr_admin_cannot_reveal_numbers_until_granted_and_reveals_are_audited(): void
    {
        $hr = $this->userFor('hr-admin', $this->employee());
        $target = $this->employee();

        app(SaveGovernmentIds::class)->handle($hr, $target, ['sss' => '3412345678']);

        try {
            app(RevealSensitiveField::class)->handle($hr, $target->fresh(), 'sss');
            $this->fail('HR Admin should not be able to reveal by default.');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }

        $hr->givePermissionTo('employee.government-id.reveal');

        $value = app(RevealSensitiveField::class)->handle($hr, $target->fresh(), 'sss');

        $this->assertSame('3412345678', $value);
        $this->assertDatabaseHas('audit_logs', ['action' => 'employee.sensitive_revealed', 'target_id' => $target->id]);
        $this->assertStringNotContainsString('3412345678', json_encode(DB::table('audit_logs')->get()));
    }

    // ---- bank accounts -------------------------------------------------------

    public function test_bank_accounts_need_verification_and_a_newer_one_supersedes_the_old(): void
    {
        $employee = $this->employee();
        $employeeUser = $this->userFor('employee', $employee);
        $hr = $this->userFor('hr-admin', $this->employee());

        $first = app(SubmitBankAccount::class)->handle($employeeUser, $employee, [
            'bank_name' => 'Bank A', 'account_name' => 'First Last', 'account_number' => '0012 3456 7890',
        ]);

        $this->assertSame('pending', $first->status);
        $this->assertNull($employee->fresh()->currentBankAccount);

        app(ReviewBankAccount::class)->handle($hr, $first, 'verify');
        $this->assertSame('verified', $first->fresh()->status);

        $second = app(SubmitBankAccount::class)->handle($employeeUser, $employee, [
            'bank_name' => 'Bank B', 'account_name' => 'First Last', 'account_number' => '9988776655443',
        ]);

        // Payroll keeps using the verified account until the new one is verified.
        $this->assertSame($first->id, $employee->fresh()->currentBankAccount->id);

        app(ReviewBankAccount::class)->handle($hr, $second, 'verify');

        $this->assertSame('superseded', $first->fresh()->status);
        $this->assertSame('verified', $second->fresh()->status);
    }

    public function test_nobody_reviews_a_bank_account_they_submitted_or_their_own(): void
    {
        $employee = $this->employee();
        $hr = $this->userFor('hr-admin', $this->employee());

        // HR enters an account on someone's behalf, then tries to verify it.
        $account = app(SubmitBankAccount::class)->handle($hr, $employee, [
            'bank_name' => 'Bank A', 'account_name' => 'First Last', 'account_number' => '0012345678900',
        ]);

        try {
            app(ReviewBankAccount::class)->handle($hr, $account, 'verify');
            $this->fail('Submitter should not be able to verify their own submission.');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }

        // HR staff can't verify an account that belongs to themselves, even when someone else submitted it.
        $hrEmployee = $this->employee();
        $hrWithRecord = $this->userFor('hr-admin', $hrEmployee);
        $own = app(SubmitBankAccount::class)->handle($hr, $hrEmployee, [
            'bank_name' => 'Bank A', 'account_name' => 'HR Person', 'account_number' => '5544332211009',
        ]);

        $this->expectException(AuthorizationException::class);

        app(ReviewBankAccount::class)->handle($hrWithRecord, $own, 'verify');
    }

    // ---- placement and lifecycle -------------------------------------------------

    public function test_changing_placement_requires_the_reassign_permission(): void
    {
        $editor = $this->userFor('employee', $this->employee());
        $editor->givePermissionTo('employee.record.edit');

        $target = $this->employee();
        $manager = $this->employee();

        app(UpdateEmployee::class)->handle($editor, $target, ['job_title' => 'Team Lead']);
        $this->assertSame('Team Lead', $target->fresh()->job_title);

        $this->expectException(AuthorizationException::class);

        app(UpdateEmployee::class)->handle($editor, $target, ['manager_id' => $manager->id]);
    }

    public function test_reporting_loops_are_rejected(): void
    {
        $hr = $this->userFor('hr-admin', $this->employee());

        $boss = $this->employee();
        $report = $this->employee(['manager_id' => $boss->id]);

        $this->expectException(ValidationException::class);

        app(UpdateEmployee::class)->handle($hr, $boss, ['manager_id' => $report->id]);
    }

    public function test_separation_is_blocked_while_there_are_direct_reports_and_deactivates_the_login(): void
    {
        $hr = $this->userFor('hr-admin', $this->employee());

        $boss = $this->employee();
        $report = $this->employee(['manager_id' => $boss->id]);
        $bossLogin = $this->userFor('employee', $boss);

        $details = ['separation_date' => now()->toDateString(), 'separation_reason' => 'Resigned'];

        try {
            app(ChangeEmployeeStatus::class)->handle($hr, $boss, 'separated', $details);
            $this->fail('Separation should be blocked while direct reports exist.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        $report->update(['manager_id' => null]);

        app(ChangeEmployeeStatus::class)->handle($hr, $boss->fresh(), 'separated', $details);

        $this->assertSame('separated', $boss->fresh()->status);
        $this->assertSame('inactive', $bossLogin->fresh()->status);
    }

    // ---- audit hygiene and onboarding --------------------------------------------

    public function test_audit_logs_never_contain_personal_details(): void
    {
        $hr = $this->userFor('hr-admin', $this->employee());

        app(CreateEmployee::class)->handle($hr, [
            'employee_number' => 'E-900',
            'first_name' => 'Secretive',
            'last_name' => 'Person',
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'job_title' => 'Analyst',
            'hire_date' => now()->toDateString(),
            'birth_date' => '1990-05-17',
            'address_line1' => '12 Secret Street',
            'personal_email' => 'secret.person@example.com',
        ]);

        $logs = json_encode(DB::table('audit_logs')->get());

        $this->assertStringNotContainsString('Secret Street', $logs);
        $this->assertStringNotContainsString('secret.person@example.com', $logs);
        $this->assertStringNotContainsString('1990-05-17', $logs);
        $this->assertDatabaseHas('audit_logs', ['action' => 'employee.created']);
    }

    public function test_onboarding_requires_the_required_fields_and_only_the_owner_can_complete_it(): void
    {
        $employee = $this->employee();
        $user = $this->userFor('employee', $employee);

        try {
            app(CompleteOnboarding::class)->handle($user, $employee, ['personal_email' => 'me@example.com', 'accept_privacy' => true]);
            $this->fail('Missing required fields should be rejected.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        $complete = [
            'personal_email' => 'me@example.com',
            'birth_date' => '1992-03-04',
            'address_line1' => '1 Sample Road',
            'city' => 'Sample City',
            'province' => 'Sample Province',
            'postal_code' => '1000',
            'phone' => '09170000000',
            'accept_privacy' => true,
        ];

        app(CompleteOnboarding::class)->handle($user, $employee, $complete);

        $this->assertNotNull($employee->fresh()->onboarding_completed_at);
        $this->assertNotNull($employee->fresh()->privacy_acknowledged_at);

        // Someone else cannot complete it on the employee's behalf.
        $other = $this->employee();
        $hr = $this->userFor('hr-admin', $this->employee());

        $this->expectException(AuthorizationException::class);

        app(CompleteOnboarding::class)->handle($hr, $other, $complete);
    }
}