<?php

namespace Tests\Feature\Audit;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\Audit\AuditCatalog;
use App\Support\Audit\AuditLogExporter;
use App\Support\Audit\AuditLogger;
use App\Support\Audit\AuditLogPresenter;
use App\Support\Audit\AuditLogQuery;
use App\Support\Settings\Settings;
use Database\Seeders\RolesAndPermissionsSeeder;
use DateTimeInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use LogicException;
use Tests\TestCase;

class AuditLogTest extends TestCase
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
            'name' => "User {$this->counter}",
            'email' => "user{$this->counter}@test.local",
            'password' => Hash::make('password1234'),
            'status' => 'active',
        ], $attributes))->save();

        $user->assignRole($role);

        return $user;
    }

    private function insertLog(string $action, ?int $actorId, DateTimeInterface $at, ?array $new = null): void
    {
        DB::table('audit_logs')->insert([
            'actor_id' => $actorId,
            'action' => $action,
            'new_values' => $new ? json_encode($new) : null,
            'created_at' => $at,
        ]);
    }

    public function test_unknown_events_are_described_automatically(): void
    {
        $catalog = app(AuditCatalog::class);

        $this->assertSame('leave', $catalog->category('leave.request_approved'));
        $this->assertSame('Leave', $catalog->categoryLabel('leave'));
        $this->assertSame('Request approved', $catalog->title('leave.request_approved'));
        $this->assertSame('info', $catalog->severity('leave.request_approved'));

        // Registered events keep their custom label and severity.
        $this->assertSame('Failed sign-in attempt', $catalog->title('auth.login_failed'));
        $this->assertSame('warning', $catalog->severity('auth.login_failed'));
    }

    public function test_a_new_module_can_log_and_the_viewer_shows_it_with_no_extra_setup(): void
    {
        $actor = $this->userWithRole('hr-admin');

        app(AuditLogger::class)->log('leave.request_approved', null, ['status' => 'pending'], ['status' => 'approved'], $actor);

        $rows = app(AuditLogPresenter::class)->present(
            app(AuditLogQuery::class)->filtered(['category' => 'leave'])->get()
        );

        $this->assertCount(1, $rows);
        $this->assertSame('Request approved', $rows[0]['title']);
        $this->assertSame('Leave', $rows[0]['category']['label']);
        $this->assertSame($actor->name, $rows[0]['actor']['name']);
        $this->assertSame(['status' => 'approved'], $rows[0]['new']);
        $this->assertArrayHasKey('leave', app(AuditCatalog::class)->categories());
    }

    public function test_filters_narrow_the_results(): void
    {
        $a = $this->userWithRole('hr-admin');
        $b = $this->userWithRole('hr-admin');

        $this->insertLog('auth.login', $a->id, now());
        $this->insertLog('auth.login_failed', $b->id, now());
        $this->insertLog('employee.created', $a->id, now()->subDays(40));

        $query = app(AuditLogQuery::class);

        $this->assertCount(3, $query->filtered([])->get());
        $this->assertCount(2, $query->filtered(['from' => now()->subDays(30)->toDateString()])->get());
        $this->assertCount(1, $query->filtered(['actor_id' => $b->id])->get());
        $this->assertCount(2, $query->filtered(['category' => 'auth'])->get());
        $this->assertSame(['auth.login_failed'], $query->filtered(['severity' => 'warning'])->pluck('action')->all());
        $this->assertCount(2, $query->filtered(['severity' => 'info'])->get());
    }

    public function test_entries_cannot_be_edited_or_deleted(): void
    {
        $this->insertLog('auth.login', null, now());
        $log = AuditLog::first();

        try {
            $log->update(['action' => 'tampered']);
            $this->fail('Audit entries must be immutable.');
        } catch (LogicException) {
            $this->assertTrue(true);
        }

        try {
            $log->delete();
            $this->fail('Audit entries must not be deletable.');
        } catch (LogicException) {
            $this->assertTrue(true);
        }

        $this->assertSame('auth.login', AuditLog::first()->action);
    }

    public function test_hr_admin_cannot_view_the_audit_log_by_default(): void
    {
        $hr = $this->userWithRole('hr-admin');
        $super = $this->userWithRole('super-admin');

        $this->assertTrue(Gate::forUser($hr)->denies('viewAny', AuditLog::class));
        $this->assertTrue(Gate::forUser($super)->allows('viewAny', AuditLog::class));
    }

    public function test_export_needs_permission_is_audited_and_neutralizes_spreadsheet_formulas(): void
    {
        $hr = $this->userWithRole('hr-admin');
        $super = $this->userWithRole('super-admin', ['name' => '=SUM(1)']);

        try {
            app(AuditLogExporter::class)->stream($hr, []);
            $this->fail('HR Admin should not be able to export by default.');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }

        $this->insertLog('auth.login', $super->id, now());

        $response = app(AuditLogExporter::class)->stream($super, []);

        ob_start();
        $response->sendContent();
        $csv = ob_get_clean();

        $this->assertStringContainsString('When,Category,Event', $csv);
        $this->assertStringContainsString("'=SUM(1)", $csv);
        $this->assertDatabaseHas('audit_logs', ['action' => 'audit.exported', 'actor_id' => $super->id]);
    }

    public function test_pruning_removes_only_old_entries_and_respects_the_retention_setting(): void
    {
        $this->insertLog('auth.login', null, now()->subDays(40));
        $this->insertLog('auth.logout', null, now()->subDays(5));

        // Default retention is 0 (keep forever).
        $this->artisan('audit:prune')->assertSuccessful();
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.login']);

        app(Settings::class)->set('audit.retention_days', 30);

        $this->artisan('audit:prune')->assertSuccessful();

        $this->assertDatabaseMissing('audit_logs', ['action' => 'auth.login']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.logout']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'audit.pruned']);
    }
}