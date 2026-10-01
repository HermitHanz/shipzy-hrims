<?php

namespace Tests\Feature\Settings;

use App\Actions\Settings\UpdateSettings;
use App\Models\User;
use App\Support\Settings\Settings;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private int $counter = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
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

    public function test_defaults_come_from_the_registry_with_the_right_types(): void
    {
        $settings = app(Settings::class);

        $this->assertSame(5, $settings->int('security.max_login_attempts'));
        $this->assertSame(12, Settings::value('security.password_min_length'));
        $this->assertSame(0, $settings->int('audit.retention_days'));
        $this->assertSame('fallback', Settings::value('nope.missing', 'fallback'));
    }

    public function test_updating_persists_refreshes_the_value_and_writes_an_audit_entry(): void
    {
        $super = $this->userWithRole('super-admin');

        $changed = app(UpdateSettings::class)->handle($super, 'security', [
            'max_login_attempts' => '8',
            'password_min_length' => '14',
        ]);

        $this->assertEqualsCanonicalizing(['max_login_attempts', 'password_min_length'], $changed);
        $this->assertSame(8, Settings::value('security.max_login_attempts'));
        $this->assertSame(14, Settings::value('security.password_min_length'));

        $row = DB::table('audit_logs')->where('action', 'settings.updated')->first();
        $this->assertSame(5, json_decode($row->old_values, true)['security.max_login_attempts']);
        $this->assertSame(8, json_decode($row->new_values, true)['security.max_login_attempts']);

        // Saving the same values again changes nothing and writes no second entry.
        $again = app(UpdateSettings::class)->handle($super, 'security', [
            'max_login_attempts' => '8',
            'password_min_length' => '14',
        ]);

        $this->assertSame([], $again);
        $this->assertSame(1, DB::table('audit_logs')->where('action', 'settings.updated')->count());
    }

    public function test_invalid_values_are_rejected_and_nothing_is_saved(): void
    {
        $super = $this->userWithRole('super-admin');

        try {
            app(UpdateSettings::class)->handle($super, 'security', ['password_min_length' => '4']);
            $this->fail('A too-short minimum should be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('password_min_length', $e->errors());
        }

        $this->assertSame(12, Settings::value('security.password_min_length'));
    }

    public function test_only_people_with_the_edit_permission_can_change_settings(): void
    {
        foreach (['employee', 'hr-admin'] as $role) {
            try {
                app(UpdateSettings::class)->handle($this->userWithRole($role), 'security', ['max_login_attempts' => '10']);
                $this->fail("{$role} should not be able to change settings.");
            } catch (AuthorizationException) {
                $this->assertTrue(true);
            }
        }

        $this->assertSame(5, Settings::value('security.max_login_attempts'));
    }

    public function test_unknown_groups_are_rejected(): void
    {
        $this->expectException(ValidationException::class);

        app(UpdateSettings::class)->handle($this->userWithRole('super-admin'), 'nonsense', ['x' => '1']);
    }

    public function test_a_new_setting_only_needs_a_registry_entry(): void
    {
        config(['hrims_settings.groups.demo' => [
            'label' => 'Demo',
            'fields' => [
                'flag' => ['label' => 'Flag', 'type' => 'boolean', 'default' => false, 'rules' => ['boolean']],
            ],
        ]]);

        $super = $this->userWithRole('super-admin');

        $this->assertFalse(Settings::value('demo.flag'));

        app(UpdateSettings::class)->handle($super, 'demo', ['flag' => '1']);
        $this->assertTrue(Settings::value('demo.flag'));

        // An unchecked checkbox sends nothing, which means false.
        app(UpdateSettings::class)->handle($super, 'demo', []);
        $this->assertFalse(Settings::value('demo.flag'));
    }

    public function test_the_login_lockout_threshold_comes_from_the_setting(): void
    {
        app(Settings::class)->set('security.max_login_attempts', 3);

        $attempt = ['email' => 'nobody@test.local', 'password' => 'wrong-password'];

        for ($i = 0; $i < 3; $i++) {
            $this->post(route('login.store'), $attempt);
        }

        $this->assertDatabaseMissing('audit_logs', ['action' => 'auth.lockout']);

        $this->post(route('login.store'), $attempt)->assertSessionHasErrors('email');

        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.lockout']);
    }
}