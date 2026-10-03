<?php

namespace App\Console\Commands;

use App\Support\Audit\AuditLogger;
use App\Support\Settings\Settings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneAuditLogs extends Command
{
    protected $signature = 'audit:prune {--days= : Override the retention setting}';

    protected $description = 'Delete audit entries older than the retention period (0 keeps everything)';

    public function handle(Settings $settings, AuditLogger $audit): int
    {
        $days = $this->option('days') !== null ? (int) $this->option('days') : $settings->int('audit.retention_days', 0);

        if ($days <= 0) {
            $this->info('Retention is unlimited. Nothing to prune.');

            return self::SUCCESS;
        }

        $cutoff = now()->subDays($days);
        $total = 0;

        // Its own connection, so the app's DB user can be denied DELETE on audit_logs
        $db = DB::connection(config('hrims_audit.prune_connection'));

        do {
            $ids = $db->table('audit_logs')->where('created_at', '<', $cutoff)->orderBy('id')->limit(5000)->pluck('id');
            $deleted = $ids->isEmpty() ? 0 : $db->table('audit_logs')->whereIn('id', $ids)->delete();
            $total += $deleted;
        } while ($deleted > 0);

        if ($total > 0) {
            $audit->log('audit.pruned', null, null, ['deleted' => $total, 'older_than' => $cutoff->toDateString()]);
        }

        $this->info("Removed {$total} audit entries older than {$days} days.");

        return self::SUCCESS;
    }
}