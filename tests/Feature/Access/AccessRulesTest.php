<?php

namespace Tests\Feature\Access;

use App\Actions\Access\AssignUserRoles;
use App\Actions\Access\CreateRole;
use App\Actions\Access\CreateUserAccount;
use App\Actions\Access\DeleteRole;
use App\Actions\Access\SetUserStatus;
use App\Actions\Access\SyncUserPermissions;
use App\Actions\Access\UpdateRole;
use App\Models\User;
use App\Support\Access\AccessGuard;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AccessRulesTest extends TestCase
{
    use RefreshDatabase;

    private int $counter = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function userWithRole(string $role, array $attributes = []): User
    {
        $this->counter++;

        $user = new User();
        $user->forceFill(array_merge([
            'name' => "Test User {$this->counter}",
            'email' => "user{$this->counter}@test.local",
            'password' => Hash::make('password1234'),
            'status' => 'active',
        ], $attributes))->save();

        $user->assignRole($role);

        return $user;
    }

    // ---- assigning roles -------------------------------------------------

    public function test_hr_admin_cannot_assign_the_super_admin_role(): void
    {
        $hr = $this->userWithRole('hr-admin');
        $employee = $this->userWithRole('employee');

        $this->expectException(ValidationException::class);

        app(AssignUserRoles::class)->handle($hr, $employee, ['super-admin']);
    }

    public function test_hr_admin_cannot_assign_a_role_at_their_own_level(): void
    {
        $hr = $this->userWithRole('hr-admin');
        $employee = $this->userWithRole('employee');

        $this->expectException(ValidationException::class);

        app(AssignUserRoles::class)->handle($hr, $employee, ['hr-admin']);
    }

    public function test_hr_admin_can_assign_a_lower_level_role(): void
    {
        $hr = $this->userWithRole('hr-admin');
        $employee = $this->userWithRole('employee');

        Role::create(['name' => 'clerk', 'guard_name' => 'web', 'label' => 'Clerk', 'level' => 20, 'is_system' => false]);

        app(AssignUserRoles::class)->handle($hr, $employee, ['employee', 'clerk']);

        $this->assertTrue($employee->fresh()->hasRole('clerk'));
    }

    public function test_hr_admin_cannot_manage_a_super_admin(): void
    {
        $hr = $this->userWithRole('hr-admin');
        $super = $this->userWithRole('super-admin');

        $this->expectException(AuthorizationException::class);

        app(AssignUserRoles::class)->handle($hr, $super, ['employee']);
    }

    public function test_nobody_can_manage_their_own_access(): void
    {
        $hr = $this->userWithRole('hr-admin');

        $this->expectException(AuthorizationException::class);

        app(AssignUserRoles::class)->handle($hr, $hr, ['employee']);
    }

    // ---- granting permissions -------------------------------------------

    public function test_hr_admin_cannot_grant_a_permission_they_do_not_hold(): void
    {
        $hr = $this->userWithRole('hr-admin');           // excluded from system.settings.*
        $employee = $this->userWithRole('employee');

        $this->expectException(ValidationException::class);

        app(SyncUserPermissions::class)->handle($hr, $employee, ['system.settings.edit']);
    }

    public function test_hr_admin_can_grant_a_permission_they_hold(): void
    {
        $hr = $this->userWithRole('hr-admin');
        $employee = $this->userWithRole('employee');

        app(SyncUserPermissions::class)->handle($hr, $employee, ['reports.report.export']);

        $this->assertTrue($employee->fresh()->hasDirectPermission('reports.report.export'));
    }

    // ---- roles -----------------------------------------------------------

    public function test_hr_admin_cannot_create_a_role_at_or_above_their_level(): void
    {
        $hr = $this->userWithRole('hr-admin');

        $this->expectException(ValidationException::class);

        app(CreateRole::class)->handle($hr, [
            'name' => 'shadow-admin', 'label' => 'Shadow Admin', 'level' => 50, 'permissions' => [],
        ]);
    }

    public function test_hr_admin_can_create_a_role_below_their_level(): void
    {
        $hr = $this->userWithRole('hr-admin');

        app(CreateRole::class)->handle($hr, [
            'name' => 'payroll-officer', 'label' => 'Payroll Officer', 'level' => 20,
            'permissions' => ['payroll.payslip.view-all'],
        ]);

        $this->assertTrue(Role::where('name', 'payroll-officer')->exists());
    }

    public function test_the_super_admin_role_cannot_be_edited(): void
    {
        $super = $this->userWithRole('super-admin');
        $role = Role::findByName('super-admin', 'web');

        $this->expectException(AuthorizationException::class);

        app(UpdateRole::class)->handle($super, $role, ['label' => 'Nope', 'level' => 100, 'permissions' => []]);
    }

    public function test_system_roles_cannot_be_deleted(): void
    {
        $super = $this->userWithRole('super-admin');

        $this->expectException(AuthorizationException::class);

        app(DeleteRole::class)->handle($super, Role::findByName('employee', 'web'));
    }

    // ---- last Super Admin ------------------------------------------------

    public function test_last_active_super_admin_is_detected(): void
    {
        $guard = app(AccessGuard::class);

        $a = $this->userWithRole('super-admin');
        $this->assertTrue($guard->isLastActiveSuperAdmin($a));

        $b = $this->userWithRole('super-admin');
        $this->assertFalse($guard->isLastActiveSuperAdmin($a));

        $b->forceFill(['status' => 'inactive'])->save();
        $this->assertTrue($guard->isLastActiveSuperAdmin($a));
        $this->assertFalse($guard->isLastActiveSuperAdmin($b));   // inactive one isn't "the last active"
    }

    public function test_super_admin_can_reactivate_another_super_admin(): void
    {
        $a = $this->userWithRole('super-admin');
        $b = $this->userWithRole('super-admin', ['status' => 'inactive']);

        app(SetUserStatus::class)->handle($a, $b, 'active');

        $this->assertSame('active', $b->fresh()->status);
    }

    // ---- account creation ------------------------------------------------

    public function test_created_accounts_must_change_password_and_are_audited_without_secrets(): void
    {
        $hr = $this->userWithRole('hr-admin');

        [$user, $temporaryPassword] = app(CreateUserAccount::class)->handle($hr, [
            'name' => 'New Hire',
            'email' => 'newhire@test.local',
            'roles' => ['employee'],
        ]);

        $this->assertTrue((bool) $user->must_change_password);
        $this->assertTrue($user->hasRole('employee'));
        $this->assertTrue(Hash::check($temporaryPassword, $user->password));

        $this->assertDatabaseHas('audit_logs', ['action' => 'user.created', 'target_id' => $user->id, 'actor_id' => $hr->id]);

        $row = DB::table('audit_logs')->where('action', 'user.created')->first();
        $this->assertStringNotContainsString($temporaryPassword, json_encode($row));
    }

    // ---- UI helpers ------------------------------------------------------

    public function test_assignable_roles_and_settable_levels_follow_the_actor_level(): void
    {
        $guard = app(AccessGuard::class);
        $hr = $this->userWithRole('hr-admin');
        $super = $this->userWithRole('super-admin');

        $this->assertSame(['employee'], $guard->assignableRoles($hr)->pluck('name')->all());
        $this->assertContains('hr-admin', $guard->assignableRoles($super)->pluck('name')->all());

        $this->assertSame(49, max($guard->settableLevels($hr)));
        $this->assertSame(99, max($guard->settableLevels($super)));
    }

    public function test_user_list_hides_accounts_above_the_actors_level(): void
    {
        $guard = app(AccessGuard::class);
        $hr = $this->userWithRole('hr-admin');
        $peer = $this->userWithRole('hr-admin');
        $employee = $this->userWithRole('employee');
        $super = $this->userWithRole('super-admin');

        $visibleToHr = $guard->viewableUsers($hr)->pluck('id')->all();

        $this->assertContains($peer->id, $visibleToHr);
        $this->assertContains($employee->id, $visibleToHr);
        $this->assertNotContains($super->id, $visibleToHr);

        $this->assertContains($super->id, $guard->viewableUsers($super)->pluck('id')->all());
    }
}