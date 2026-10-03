<?php

namespace Tests\Feature\Access;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * hrims:create-super-admin bootstraps the first account. The operator types the password
 * themselves, so no forced change follows, but the creation is still audited.
 */
class CreateSuperAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_audited_super_admin_who_is_not_forced_to_change_their_password(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->artisan('hrims:create-super-admin', ['--name' => 'Root Admin', '--email' => 'root@test.local'])
            ->expectsQuestion('Password (minimum 12 characters)', 'a-long-password')
            ->expectsQuestion('Confirm password', 'a-long-password')
            ->assertSuccessful();

        $user = User::where('email', 'root@test.local')->sole();

        $this->assertTrue($user->hasRole('super-admin'));
        $this->assertSame('active', $user->status);
        $this->assertFalse($user->must_change_password);

        $log = DB::table('audit_logs')->where('action', 'user.super_admin_created')->sole();

        $this->assertNull($log->actor_id);
        $this->assertSame('user', $log->target_type);
        $this->assertSame($user->id, (int) $log->target_id);
        $this->assertSame(['email' => 'root@test.local'], json_decode($log->new_values, true));
    }

    public function test_nothing_is_created_or_audited_when_the_input_is_invalid(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->artisan('hrims:create-super-admin', ['--name' => 'Root Admin', '--email' => 'root@test.local'])
            ->expectsQuestion('Password (minimum 12 characters)', 'short')
            ->expectsQuestion('Confirm password', 'short')
            ->assertFailed();

        $this->assertDatabaseMissing('users', ['email' => 'root@test.local']);
        $this->assertDatabaseMissing('audit_logs', ['action' => 'user.super_admin_created']);
    }
}
