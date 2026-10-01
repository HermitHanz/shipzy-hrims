<?php

namespace App\Support\Audit;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Turns raw action names ("leave.request_approved") into something readable.
 * Works with zero configuration; config/hrims_audit.php only refines labels and severity.
 */
class AuditCatalog
{
    public const SEVERITIES = ['info' => 'Info', 'notice' => 'Notice', 'warning' => 'Warning'];

    public function category(string $action): string
    {
        return Str::before($action, '.');
    }

    public function categoryLabel(string $category): string
    {
        return config('hrims_audit.categories', [])[$category] ?? Str::headline($category);
    }

    public function title(string $action): string
    {
        $event = config('hrims_audit.events', [])[$action] ?? null;

        if (isset($event['label'])) {
            return $event['label'];
        }

        return (string) Str::of(Str::after($action, '.'))->replace('_', ' ')->lower()->ucfirst();
    }

    public function severity(string $action): string
    {
        return config('hrims_audit.events', [])[$action]['severity'] ?? 'info';
    }

    /** @return array<string, string> category key => label, for the category filter */
    public function categories(): array
    {
        return collect($this->knownActions())
            ->map(fn (string $action) => $this->category($action))
            ->merge(array_keys(config('hrims_audit.categories', [])))
            ->unique()
            ->mapWithKeys(fn (string $key) => [$key => $this->categoryLabel($key)])
            ->sort()
            ->all();
    }

    /** @return array<string, array<string, string>> category key => [action => title], for the event filter */
    public function actions(): array
    {
        return collect($this->knownActions())
            ->sort()
            ->groupBy(fn (string $action) => $this->category($action))
            ->map(fn ($group) => $group->mapWithKeys(fn (string $action) => [$action => $this->title($action)])->all())
            ->all();
    }

    /** @return array<int, string> every action that appears in the log or is named in the registry */
    public function knownActions(): array
    {
        $seen = Cache::remember('hrims.audit.actions', 60, fn () => AuditLog::query()->distinct()->pluck('action')->all());

        return collect($seen)
            ->merge(array_keys(config('hrims_audit.events', [])))
            ->unique()
            ->values()
            ->all();
    }

    /** @return array<int, string> actions the registry marks with the given severity */
    public function actionsWithSeverity(string $severity): array
    {
        return collect(config('hrims_audit.events', []))
            ->filter(fn (array $event) => ($event['severity'] ?? 'info') === $severity)
            ->keys()
            ->all();
    }
}