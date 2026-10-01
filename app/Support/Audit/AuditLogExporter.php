<?php

namespace App\Support\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CSV export of the filtered log. Requires system.audit-log.export, records that the export
 * happened (filters only, never contents), streams in chunks, and is capped.
 */
class AuditLogExporter
{
    public const MAX_ROWS = 50000;

    public function __construct(
        private AuditLogQuery $query,
        private AuditLogPresenter $presenter,
        private AuditLogger $audit,
    ) {
    }

    public function stream(User $actor, array $filters): StreamedResponse
    {
        Gate::forUser($actor)->authorize('export', AuditLog::class);

        $this->audit->log('audit.exported', null, null, ['filters' => array_filter($filters)], $actor);

        $filename = 'audit-log-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($filters) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['When', 'Category', 'Event', 'Severity', 'Actor', 'Actor email', 'Target', 'IP address', 'Details']);

            $written = 0;

            $this->query->filtered($filters)->reorder()->chunkById(500, function ($logs) use ($out, &$written) {
                foreach ($this->presenter->present($logs) as $row) {
                    if ($written >= self::MAX_ROWS) {
                        return false;
                    }

                    $details = ($row['old'] || $row['new'])
                        ? json_encode(['old' => $row['old'], 'new' => $row['new']], JSON_UNESCAPED_UNICODE)
                        : '';

                    fputcsv($out, array_map([$this, 'cell'], [
                        $row['when']->format('Y-m-d H:i:s'),
                        $row['category']['label'],
                        $row['title'],
                        $row['severity'],
                        $row['actor']['name'] ?? 'System',
                        $row['actor']['email'] ?? '',
                        $row['target'] ? ($row['target']['type'] . ': ' . $row['target']['label']) : '',
                        $row['ip'] ?? '',
                        $details,
                    ]));

                    $written++;
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }

    /** Neutralize spreadsheet formulas (values starting with = + - @) in exported cells. */
    private function cell(mixed $value): string
    {
        $value = (string) $value;

        return $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'" . $value : $value;
    }
}