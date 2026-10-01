<?php

namespace App\Support\Audit;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

/**
 * Turns log rows into plain arrays the views (and the CSV export) can print without
 * knowing anything about models. Target labels are resolved in bulk, one query per type.
 */
class AuditLogPresenter
{
    public function __construct(private AuditCatalog $catalog)
    {
    }

    /**
     * @param  iterable<AuditLog>  $logs
     * @return array<int, array<string, mixed>>
     */
    public function present(iterable $logs): array
    {
        $logs = collect($logs);
        $targets = $this->resolveTargets($logs);

        return $logs->map(function (AuditLog $log) use ($targets) {
            $category = $this->catalog->category($log->action);
            $target = null;

            if ($log->target_type) {
                $hit = $targets[$log->target_type][$log->target_id] ?? null;

                $target = [
                    'type' => $log->target_type,
                    'id' => $log->target_id,
                    'label' => $hit['label'] ?? ($log->target_id ? '#' . $log->target_id : null),
                    'url' => $hit['url'] ?? null,
                ];
            }

            return [
                'id' => $log->id,
                'when' => $log->created_at,
                'action' => $log->action,
                'title' => $this->catalog->title($log->action),
                'category' => ['key' => $category, 'label' => $this->catalog->categoryLabel($category)],
                'severity' => $this->catalog->severity($log->action),
                'actor' => $log->actor
                    ? ['id' => $log->actor->id, 'name' => $log->actor->name, 'email' => $log->actor->email]
                    : null,
                'target' => $target,
                'ip' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'old' => $log->old_values,
                'new' => $log->new_values,
            ];
        })->all();
    }

    /** @return array<string, array<int|string, array{label: string, url: string|null}>> */
    private function resolveTargets(Collection $logs): array
    {
        $registry = config('hrims_audit.targets', []);
        $resolved = [];

        foreach ($logs->whereNotNull('target_type')->groupBy('target_type') as $type => $group) {
            $definition = $registry[$type] ?? null;

            if (! $definition || ! class_exists($definition['model'])) {
                continue;
            }

            $model = $definition['model'];
            $query = $model::query()->whereIn('id', $group->pluck('target_id')->filter()->unique()->all());

            if (in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
                $query->withTrashed();
            }

            foreach ($query->get() as $record) {
                $label = $record->getAttribute($definition['label']);

                if (! filled($label) && isset($definition['fallback'])) {
                    $label = $record->getAttribute($definition['fallback']);
                }

                $resolved[$type][$record->getKey()] = [
                    'label' => filled($label) ? (string) $label : '#' . $record->getKey(),
                    'url' => ! empty($definition['route']) && Route::has($definition['route'])
                        ? route($definition['route'], $record->getKey())
                        : null,
                ];
            }
        }

        return $resolved;
    }
}