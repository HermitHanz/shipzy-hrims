@php
    $sevVariant = ['info' => 'gray', 'notice' => 'yellow', 'warning' => 'red'];
    $f          = fn ($key) => (string) ($filters[$key] ?? '');
    $safeUrl    = fn ($u) => is_string($u) && \Illuminate\Support\Str::startsWith($u, ['/', 'http://', 'https://']);
    $fmt        = function ($v): string {
        if ($v === null) return 'null';
        if (is_bool($v)) return $v ? 'true' : 'false';
        if (is_scalar($v)) return (string) $v;
        return json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    };
    $hasFilters = request()->hasAny(['from', 'to', 'actor_id', 'category', 'action', 'severity', 'target_type', 'target_id', 'ip']);
@endphp

<x-layouts.app title="Audit log">
    <x-slot:header>
        <x-ui.page-header title="Audit log" subtitle="A record of sensitive actions across the system." />
    </x-slot:header>

    <x-ui.card>
        <form method="GET" action="{{ route('admin.audit-logs.index') }}" role="search" class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-ui.input name="from" type="date" label="From" :value="$f('from')" />
                <x-ui.input name="to" type="date" label="To" :value="$f('to')" />
                <x-ui.select name="category" label="Category" placeholder="All categories">
                    @foreach ($categories as $key => $label)
                        <option value="{{ $key }}" @selected($f('category') === (string) $key)>{{ $label }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.select name="severity" label="Severity" placeholder="Any severity">
                    @foreach ($severities as $key => $label)
                        <option value="{{ $key }}" @selected($f('severity') === (string) $key)>{{ $label }}</option>
                    @endforeach
                </x-ui.select>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-ui.select name="action" label="Event" placeholder="Any event">
                    @foreach ($actionGroups as $group)
                        <optgroup label="{{ $group['label'] }}">
                            @foreach ($group['actions'] as $key => $title)
                                <option value="{{ $key }}" @selected($f('action') === (string) $key)>{{ $title }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </x-ui.select>
                <x-ui.select name="actor_id" label="Actor" placeholder="Anyone">
                    <option value="system" @selected($f('actor_id') === 'system')>System / unknown</option>
                    @foreach ($actors as $a)
                        <option value="{{ $a['id'] }}" @selected($f('actor_id') === (string) $a['id'])>{{ $a['name'] }}@if (! empty($a['email'])) ({{ $a['email'] }})@endif</option>
                    @endforeach
                </x-ui.select>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-ui.select name="target_type" label="Target type" placeholder="Any target">
                    @foreach ($targetTypes as $key => $label)
                        <option value="{{ $key }}" @selected($f('target_type') === (string) $key)>{{ $label }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.input name="target_id" label="Target ID" :value="$f('target_id')" inputmode="numeric" autocomplete="off" />
                <x-ui.input name="ip" label="IP address" :value="$f('ip')" autocomplete="off" />
                <div class="flex flex-wrap items-end gap-2">
                    <x-ui.button type="submit">Apply filters</x-ui.button>
                    @if ($hasFilters)
                        <x-ui.button variant="secondary" :href="route('admin.audit-logs.index')">Reset</x-ui.button>
                    @endif
                </div>
            </div>
        </form>

        @can('export', \App\Models\AuditLog::class)
            <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-4">
                <p class="text-xs text-slate-500">Export downloads a CSV of the filters currently applied.</p>
                <x-ui.button variant="secondary" :href="route('admin.audit-logs.export', $filters)">
                    <x-ui.icon name="document" class="size-4" /> Export CSV
                </x-ui.button>
            </div>
        @endcan
    </x-ui.card>

    <x-ui.card class="mt-6">
        <div class="-mx-5 -my-5">
            @if (empty($rows))
                <x-ui.empty-state title="No audit entries match" description="Widen the date range or clear some filters." icon="document" />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full divide-y divide-slate-100 text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                            <tr>
                                @foreach (['Time', 'Severity', 'Event', 'Category', 'Actor', 'Target', 'IP'] as $h)
                                    <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium">{{ $h }}</th>
                                @endforeach
                            </tr>
                        </thead>

                        @foreach ($rows as $row)
                            @php
                                $old  = is_array($row['old'] ?? null) ? $row['old'] : [];
                                $new  = is_array($row['new'] ?? null) ? $row['new'] : [];
                                $keys = array_values(array_unique(array_merge(array_keys($old), array_keys($new))));
                                $cell = fn (array $arr, $k) => array_key_exists($k, $arr) ? $fmt($arr[$k]) : null;
                                $target = $row['target'] ?? null;
                            @endphp
                            <tbody x-data="{ open: false }">
                                <tr class="align-top hover:bg-slate-50">
                                    <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-600" title="{{ $row['when']->diffForHumans() }}">
                                        {{ $row['when']->format('M j, Y') }}<br>{{ $row['when']->format('g:i:s A') }}
                                    </td>
                                    <td class="px-4 py-3"><x-ui.badge :variant="$sevVariant[$row['severity']] ?? 'gray'">{{ $severities[$row['severity']] ?? ucfirst($row['severity']) }}</x-ui.badge></td>
                                    <td class="px-4 py-3">
                                        <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open" aria-controls="audit-detail-{{ $row['id'] }}"
                                                class="flex items-start gap-1.5 text-left font-medium hover:text-brand-700">
                                            <x-ui.icon name="chevron-down" class="mt-0.5 size-4 shrink-0 text-slate-400 transition" x-bind:class="open ? '' : '-rotate-90'" />
                                            <span>{{ $row['title'] }}</span>
                                        </button>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">{{ $row['category']['label'] ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        @if ($row['actor'])
                                            <p class="font-medium">{{ $row['actor']['name'] }}</p>
                                            <p class="text-xs text-slate-500">{{ $row['actor']['email'] }}</p>
                                        @else
                                            <span class="text-slate-500">System / unknown</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($target)
                                            @if ($safeUrl($target['url'] ?? null))
                                                <a href="{{ $target['url'] }}" class="font-medium text-brand-700 hover:underline">{{ $target['label'] }}</a>
                                            @else
                                                <span>{{ $target['label'] }}</span>
                                            @endif
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-600">{{ $row['ip'] ?: '—' }}</td>
                                </tr>

                                <tr id="audit-detail-{{ $row['id'] }}" x-show="open" x-cloak class="bg-slate-50/60">
                                    <td colspan="7" class="px-4 py-4">
                                        <div class="grid gap-6 lg:grid-cols-3">
                                            <div class="lg:col-span-2">
                                                <h3 class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Changes</h3>
                                                @if ($keys)
                                                    <div class="overflow-x-auto rounded-lg bg-white ring-1 ring-slate-200">
                                                        <table class="w-full text-left text-xs">
                                                            <thead class="border-b border-slate-100 text-slate-500">
                                                                <tr><th scope="col" class="px-3 py-2 font-medium">Field</th><th scope="col" class="px-3 py-2 font-medium">Old</th><th scope="col" class="px-3 py-2 font-medium">New</th></tr>
                                                            </thead>
                                                            <tbody class="divide-y divide-slate-100">
                                                                @foreach ($keys as $k)
                                                                    @php $o = $cell($old, $k); $n = $cell($new, $k); @endphp
                                                                    <tr class="align-top">
                                                                        <th scope="row" class="whitespace-nowrap px-3 py-2 font-mono font-medium text-slate-700">{{ $k }}</th>
                                                                        <td class="max-w-xs px-3 py-2">
                                                                            @if (is_null($o))<span class="text-slate-300">—</span>@else<span class="block max-h-40 overflow-auto whitespace-pre-wrap break-all text-slate-700">{{ $o }}</span>@endif
                                                                        </td>
                                                                        <td class="max-w-xs px-3 py-2">
                                                                            @if (is_null($n))<span class="text-slate-300">—</span>@else<span class="block max-h-40 overflow-auto whitespace-pre-wrap break-all text-slate-900">{{ $n }}</span>@endif
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                @else
                                                    <p class="text-sm text-slate-500">No value changes were recorded for this entry.</p>
                                                @endif
                                            </div>

                                            <dl class="space-y-3 text-xs">
                                                <div><dt class="text-slate-500">Event key</dt><dd class="mt-0.5 break-all font-mono">{{ $row['action'] }}</dd></div>
                                                @if ($target)
                                                    <div><dt class="text-slate-500">Target</dt><dd class="mt-0.5 break-all">{{ $target['type'] }} #{{ $target['id'] }}</dd></div>
                                                @endif
                                                <div><dt class="text-slate-500">User agent</dt><dd class="mt-0.5 break-all text-slate-700">{{ $row['user_agent'] ?: '—' }}</dd></div>
                                            </dl>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        @endforeach
                    </table>
                </div>

                <nav class="flex items-center justify-between border-t border-slate-100 px-5 py-3" aria-label="Pagination">
                    @if ($paginator->onFirstPage())
                        <span class="inline-flex cursor-not-allowed items-center rounded-lg px-3 py-1.5 text-sm font-medium text-slate-400 ring-1 ring-inset ring-slate-200" aria-disabled="true">Previous</span>
                    @else
                        <x-ui.button variant="secondary" :href="$paginator->previousPageUrl()" class="!px-3 !py-1.5">Previous</x-ui.button>
                    @endif

                    <p class="text-sm text-slate-500">Page {{ $paginator->currentPage() }}</p>

                    @if ($paginator->hasMorePages())
                        <x-ui.button variant="secondary" :href="$paginator->nextPageUrl()" class="!px-3 !py-1.5">Next</x-ui.button>
                    @else
                        <span class="inline-flex cursor-not-allowed items-center rounded-lg px-3 py-1.5 text-sm font-medium text-slate-400 ring-1 ring-inset ring-slate-200" aria-disabled="true">Next</span>
                    @endif
                </nav>
            @endif
        </div>
    </x-ui.card>
</x-layouts.app>