@props([
    'granted'   => [],     // keys the subject holds directly / on the role. These are SUBMITTED.
    'inherited' => [],     // key => [role labels]. Shown checked + disabled, NOT submitted (unless also in $granted).
    'name'      => 'permissions',
    'readonly'  => false,  // view-only: nothing is submitted
    'collapsed' => false,
])
@php
    use Illuminate\Support\Str;

    $actor    = auth()->user();
    $guard    = app(\App\Support\Access\AccessGuard::class);
    $registry = config('hrims_permissions.modules', []);
    $granted  = array_values(array_unique((array) $granted));

    $labelFor = function (string $action): string {
        if (preg_match('/^(.+)-(own|team|department|all)$/', $action, $m)) {
            return Str::headline($m[1]) . ' (' . $m[2] . ')';
        }
        return Str::headline($action);
    };
@endphp

<div class="space-y-4">
    @foreach ($registry as $module => $resources)
        <section x-data="permissionSection({{ \Illuminate\Support\Js::from(['open' => ! $collapsed]) }})"
                 x-on:change="refresh()"
                 class="rounded-xl bg-white ring-1 ring-slate-200">
            <header class="flex items-center gap-3 px-4 py-3">
                <button type="button" x-on:click="open = ! open" x-bind:aria-expanded="open" aria-controls="pm-{{ $name }}-{{ $module }}"
                        class="flex flex-1 items-center gap-2 text-left">
                    <x-ui.icon name="chevron-down" class="size-4 text-slate-400 transition" x-bind:class="open ? '' : '-rotate-90'" />
                    <span class="text-sm font-semibold">{{ Str::headline($module) }}</span>
                    <span class="text-xs text-slate-500"><span x-text="checked"></span> / <span x-text="total"></span> granted</span>
                </button>
                @unless ($readonly)
                    <label class="flex items-center gap-2 text-xs font-medium text-slate-600">
                        <input type="checkbox" x-ref="moduleToggle" x-on:change="toggleAll($event)"
                               class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                               aria-label="Select all {{ Str::headline($module) }} permissions">
                        All
                    </label>
                @endunless
            </header>

            <div id="pm-{{ $name }}-{{ $module }}" x-ref="body" x-show="open">
                @foreach ($resources as $resource => $actions)
                    <div data-row class="flex flex-col gap-3 border-t border-slate-100 px-4 py-3 sm:flex-row sm:items-start">
                        <div class="flex w-44 shrink-0 items-center gap-2">
                            @unless ($readonly)
                                <input type="checkbox" data-row-toggle x-on:change="toggleRow($event)"
                                       class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500"
                                       aria-label="Select all {{ Str::headline($resource) }} permissions in {{ Str::headline($module) }}">
                            @endunless
                            <span class="text-sm font-medium">{{ Str::headline($resource) }}</span>
                        </div>

                        <div class="flex flex-1 flex-wrap gap-x-5 gap-y-2">
                            @foreach ($actions as $action)
                                @php
                                    $key           = "{$module}.{$resource}.{$action}";
                                    $direct        = in_array($key, $granted, true);
                                    $via           = $inherited[$key] ?? [];
                                    $inheritedOnly = ! $direct && ! empty($via);
                                    $canGrant      = ! $readonly && $guard->canGrantPermission($actor, $key);
                                    $disabled      = $readonly || $inheritedOnly || ! $canGrant;
                                    $tip = $inheritedOnly ? 'Inherited via ' . implode(', ', $via)
                                         : ((! $readonly && ! $canGrant) ? 'You cannot grant this permission' : 'Permission');
                                @endphp

                                <label class="inline-flex items-center gap-2 text-sm {{ $disabled ? 'cursor-not-allowed text-slate-500' : 'cursor-pointer text-slate-700' }}"
                                       title="{{ $tip }}: {{ $key }}">
                                    <input type="checkbox" data-perm
                                           @unless ($disabled) name="{{ $name }}[]" value="{{ $key }}" @endunless
                                           @checked($direct || $inheritedOnly)
                                           @disabled($disabled)
                                           class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500 disabled:opacity-60">
                                    <span>{{ $labelFor($action) }}</span>
                                    @if ($inheritedOnly)
                                        <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-600">via {{ implode(', ', $via) }}</span>
                                    @elseif ($direct && ! empty($via))
                                        <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-600">also via {{ implode(', ', $via) }}</span>
                                    @endif
                                </label>

                                {{-- Held but not grantable by this actor: the sync REPLACES the set, so resubmit it. --}}
                                @if (! $readonly && $direct && ! $canGrant)
                                    <input type="hidden" name="{{ $name }}[]" value="{{ $key }}">
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach
</div>