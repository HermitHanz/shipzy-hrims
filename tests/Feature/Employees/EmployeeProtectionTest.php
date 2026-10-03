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
use App\Support\Employees\Options;
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
            'account_type' => 'savings', 'bank_name' => 'Bank A', 'account_name' => 'First Last', 'account_number' => '0012 3456 7890',
        ]);

        $this->assertSame('pending', $first->status);
        $this->assertNull($employee->fresh()->currentBankAccount);

        app(ReviewBankAccount::class)->handle($hr, $first, 'verify');
        $this->assertSame('verified', $first->fresh()->status);

        $second = app(SubmitBankAccount::class)->handle($employeeUser, $employee, [
            'account_type' => 'savings', 'bank_name' => 'Bank B', 'account_name' => 'First Last', 'account_number' => '9988776655443',
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
            'account_type' => 'savings', 'bank_name' => 'Bank A', 'account_name' => 'First Last', 'account_number' => '0012345678900',
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
            'account_type' => 'savings', 'bank_name' => 'Bank A', 'account_name' => 'HR Person', 'account_number' => '5544332211009',
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

    // ---- regression tests from the screen review --------------------------------

    public function test_bank_reveal_cannot_be_pointed_at_another_employees_account(): void
    {
        $hr = $this->userFor('hr-admin', $this->employee());
        $hr->givePermissionTo('employee.bank.reveal');

        $a = $this->employee();
        $b = $this->employee();

        $account = app(SubmitBankAccount::class)->handle($this->userFor('employee', $b), $b, [
            'account_type' => 'savings', 'bank_name' => 'Bank A', 'account_name' => 'Person B', 'account_number' => '0012345678900',
        ]);

        // Employee A's route with employee B's account id must fail.
        try {
            app(RevealSensitiveField::class)->handle($hr, $a, 'bank_account', $account->id);
            $this->fail('Revealing another employee\'s account through a different employee must be rejected.');
        } catch (ValidationException) {
            $this->assertTrue(true);
        }

        // The correct pairing works.
        $this->assertSame('0012345678900', app(RevealSensitiveField::class)->handle($hr, $b, 'bank_account', $account->id));
    }

    public function test_government_id_keys_that_are_absent_are_kept_and_null_keys_are_cleared(): void
    {
        $hr = $this->userFor('hr-admin', $this->employee());
        $target = $this->employee();

        app(SaveGovernmentIds::class)->handle($hr, $target, ['sss' => '3412345678', 'tin' => '123456789']);

        // Only philhealth is sent: sss and tin must stay untouched.
        app(SaveGovernmentIds::class)->handle($hr, $target->fresh(), ['philhealth' => '123456789012']);

        $record = $target->fresh()->governmentId;
        $this->assertTrue($record->isSet('sss'));
        $this->assertTrue($record->isSet('tin'));
        $this->assertTrue($record->isSet('philhealth'));

        // An explicit null clears just that ID.
        app(SaveGovernmentIds::class)->handle($hr, $target->fresh(), ['tin' => null]);

        $record = $target->fresh()->governmentId;
        $this->assertFalse($record->isSet('tin'));
        $this->assertTrue($record->isSet('sss'));
    }

    public function test_bank_submissions_need_a_valid_account_type(): void
    {
        $employee = $this->employee();
        $user = $this->userFor('employee', $employee);

        $base = ['bank_name' => 'Bank A', 'account_name' => 'First Last', 'account_number' => '0012345678900'];

        foreach ([[], ['account_type' => 'crypto']] as $extra) {
            try {
                app(SubmitBankAccount::class)->handle($user, $employee, $base + $extra);
                $this->fail('A missing or unknown account type should be rejected.');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('account_type', $e->errors());
            }
        }

        $account = app(SubmitBankAccount::class)->handle($user, $employee, $base + ['account_type' => 'payroll']);

        $this->assertSame('payroll', $account->account_type);
    }

    // ---- over HTTP (request -> controller -> action) -----------------------

    /** HR user whose own onboarding is done, so RequireOnboarding lets them through. */
    private function onboardedHr(): User
    {
        $employee = $this->employee();
        $employee->forceFill(['onboarding_completed_at' => now()])->save();

        return $this->userFor('hr-admin', $employee);
    }

    public function test_a_bank_account_submitted_through_the_form_keeps_its_account_type(): void
    {
        $hr = $this->onboardedHr();
        $target = $this->employee();
        $form = ['bank_name' => 'Bank A', 'account_name' => 'First Last', 'account_number' => '0012 3456 7890'];

        $this->actingAs($hr)->post(route('employees.bank-accounts.store', $target), $form)
            ->assertSessionHasErrors('account_type');        $this->actingAs($hr)->post(route('employees.bank-accounts.store', $target), $form + ['account_type' => 'savings'])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('employees.show', $target).'#bank');

        $account = $target->bankAccounts()->sole();
        $this->assertSame('savings', $account->account_type);
        $this->assertSame('pending', $account->status);
    }

    public function test_an_employee_can_be_created_without_a_work_email(): void
    {
        $hr = $this->onboardedHr();

        $this->actingAs($hr)->post(route('employees.store'), [
            'employee_number' => 'E-NOMAIL',
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
            'job_title' => 'Warehouse Staff',
            'employment_type' => array_key_first(Options::types()),
            'branch_id' => $this->branch->id,
            'department_id' => $this->department->id,
            'hire_date' => now()->toDateString(),
        ])->assertSessionHasNoErrors();

        $this->assertNull(Employee::where('employee_number', 'E-NOMAIL')->sole()->work_email);
    }

    public function test_employee_search_matches_the_full_name(): void
    {
        $hr = $this->onboardedHr();
        $this->employee(['first_name' => 'Maria', 'last_name' => 'Santos']);
        $this->employee(['first_name' => 'Juan', 'last_name' => 'Cruz']);

        $this->actingAs($hr)->get(route('employees.index', ['q' => 'Maria Santos']))
            ->assertOk()
            ->assertSee('Santos, Maria')
            ->assertDontSee('Cruz, Juan');
    }

    // ---- scoped reviewers and filters --------------------------------------

    /** A manager whose role only reaches their direct reports, who can also verify bank accounts. */
    private function teamVerifier(Employee $manager): User
    {
        $user = $this->userFor('employee', $manager);
        $user->givePermissionTo(['employee.record.view-team', 'employee.bank.verify']);
        $manager->forceFill(['onboarding_completed_at' => now()])->save();

        return $user;
    }

    public function test_bank_reviewers_only_see_and_review_employees_within_their_scope(): void
    {
        $hr = $this->onboardedHr();
        $manager = $this->employee();
        $report = $this->employee(['first_name' => 'Rita', 'last_name' => 'Report', 'manager_id' => $manager->id]);
        $outsider = $this->employee(['first_name' => 'Otto', 'last_name' => 'Outsider']);
        $verifier = $this->teamVerifier($manager);

        $form = ['account_type' => 'savings', 'bank_name' => 'Bank A', 'account_name' => 'Name', 'account_number' => '0012345678900'];
        $reportAccount = app(SubmitBankAccount::class)->handle($hr, $report, $form);
        $outsiderAccount = app(SubmitBankAccount::class)->handle($hr, $outsider, $form);

        $this->actingAs($verifier)->get(route('employees.bank-verification.index'))
            ->assertOk()
            ->assertSee('Report, Rita')
            ->assertDontSee('Outsider, Otto');

        $this->actingAs($verifier)->post(route('employees.bank-accounts.review', [$outsider, $outsiderAccount]), ['decision' => 'verify'])
            ->assertForbidden();
        $this->assertSame('pending', $outsiderAccount->fresh()->status);

        $this->actingAs($verifier)->post(route('employees.bank-accounts.review', [$report, $reportAccount]), ['decision' => 'verify'])
            ->assertSessionHasNoErrors();
        $this->assertSame('verified', $reportAccount->fresh()->status);
    }

    public function test_employee_list_filters_only_offer_units_the_viewer_can_see(): void
    {
        $manager = $this->employee();
        $this->employee(['manager_id' => $manager->id]);
        $north = Branch::create(['code' => 'NORTH', 'name' => 'North Branch']);
        $this->employee(['branch_id' => $north->id]);

        $this->actingAs($this->teamVerifier($manager))->get(route('employees.index'))
            ->assertOk()
            ->assertSee('Main Office')
            ->assertDontSee('North Branch');

        $this->actingAs($this->onboardedHr())->get(route('employees.index'))
            ->assertSee('Main Office')
            ->assertSee('North Branch');
    }
}