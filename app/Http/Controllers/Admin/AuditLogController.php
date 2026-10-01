<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AuditLogFilterRequest;
use App\Models\AuditLog;
use App\Support\Audit\AuditCatalog;
use App\Support\Audit\AuditLogExporter;
use App\Support\Audit\AuditLogPresenter;
use App\Support\Audit\AuditLogQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function index(AuditLogFilterRequest $request, AuditLogQuery $query, AuditLogPresenter $presenter, AuditCatalog $catalog): View
    {
        Gate::authorize('viewAny', AuditLog::class);

        $filters   = $request->filters();
        $paginator = $query->filtered($filters)->simplePaginate(50)->withQueryString();

        $categories   = $catalog->categories();
        $actionGroups = collect($catalog->actions())->map(fn ($actions, $category) => [
            'label'   => $categories[$category] ?? Str::headline((string) $category),
            'actions' => $actions,
        ]);

        return view('admin.audit-logs.index', [
            'rows'         => $presenter->present($paginator->items()),
            'paginator'    => $paginator,
            'filters'      => $filters,
            'categories'   => $categories,
            'actionGroups' => $actionGroups,
            'severities'   => AuditCatalog::SEVERITIES,
            'actors'       => $this->actors($query->actorOptions()),
            'targetTypes'  => $this->options($query->targetTypes()),
        ]);
    }

    public function export(AuditLogFilterRequest $request, AuditLogExporter $exporter): StreamedResponse
    {
        Gate::authorize('export', AuditLog::class);

        // The exporter authorizes and audits itself; we pass the same filters shown on screen
        return $exporter->stream($request->user(), $request->filters());
    }

    /** Accepts models, [id, name] arrays, or id => name maps. */
    private function actors(iterable $items): array
    {
        $out = [];
        foreach ($items as $key => $item) {
            if (is_object($item)) {
                $out[] = ['id' => $item->id, 'name' => $item->name, 'email' => $item->email ?? null];
            } elseif (is_array($item)) {
                $out[] = ['id' => $item['id'], 'name' => $item['name'] ?? ('#' . $item['id']), 'email' => $item['email'] ?? null];
            } else {
                $out[] = ['id' => $key, 'name' => (string) $item, 'email' => null];
            }
        }

        return $out;
    }

    /** Accepts a list of values or a value => label map. */
    private function options(iterable $items): array
    {
        $out = [];
        foreach ($items as $key => $value) {
            is_int($key) ? $out[$value] = class_basename((string) $value) : $out[$key] = $value;
        }

        return $out;
    }
}