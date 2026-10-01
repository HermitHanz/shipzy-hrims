<?php

namespace App\Support\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Builds the viewer's query from filters. Newest first. Paginate with simplePaginate() (no
 * total count) and default to a recent date range so the indexes do the work on big tables.
 *
 * Filters: from, to (dates), actor_id (a user id, or 'system' for entries with no actor),
 * category, action, severity (info|notice|warning), target_type, target_id, ip.
 */
class AuditLogQuery
{
    public function __construct(private AuditCatalog $catalog)
    {
    }

    public function filtered(array $filters): Builder
    {
        $query = AuditLog::query()->with('actor:id,name,email');

        if (! empty($filters['from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['from'])->startOfDay());
        }

        if (! empty($filters['to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['to'])->endOfDay());
        }

        if (! empty($filters['actor_id'])) {
            $filters['actor_id'] === 'system'
                ? $query->whereNull('actor_id')
                : $query->where('actor_id', (int) $filters['actor_id']);
        }

        if (! empty($filters['category'])) {
            $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $filters['category']);
            $query->where('action', 'like', $escaped . '.%');
        }

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['severity'])) {
            if ($filters['severity'] === 'info') {
                $flagged = array_merge(
                    $this->catalog->actionsWithSeverity('notice'),
                    $this->catalog->actionsWithSeverity('warning'),
                );
                $query->whereNotIn('action', $flagged);
            } else {
                $query->whereIn('action', $this->catalog->actionsWithSeverity($filters['severity']));
            }
        }

        if (! empty($filters['target_type'])) {
            $query->where('target_type', $filters['target_type']);

            if (! empty($filters['target_id'])) {
                $query->where('target_id', (int) $filters['target_id']);
            }
        }

        if (! empty($filters['ip'])) {
            $query->where('ip_address', $filters['ip']);
        }

        return $query->orderByDesc('created_at')->orderByDesc('id');
    }

    /** Users who appear as an actor in the log, for the actor filter (capped). */
    public function actorOptions(): Collection
    {
        return User::query()
            ->whereIn('id', AuditLog::query()->whereNotNull('actor_id')->select('actor_id')->distinct())
            ->orderBy('name')
            ->limit(500)
            ->get(['id', 'name', 'email']);
    }

    /** @return array<string, string> target type => readable name, for the target filter */
    public function targetTypes(): array
    {
        return collect(array_keys(config('hrims_audit.targets', [])))
            ->mapWithKeys(fn (string $type) => [$type => ucfirst(str_replace('_', ' ', $type))])
            ->all();
    }
}