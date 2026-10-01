@php
    $keys      = array_keys($groups);
    $submitted = old('_group');
    $start     = $submitted ?: session('settings_group');
@endphp
<x-layouts.app title="Settings">
    <x-slot:header>
        <x-ui.page-header title="Settings" subtitle="Company-wide configuration." />
    </x-slot:header>

    @error('group')
        <div role="alert" class="mb-6 rounded-xl bg-red-50 p-4 text-sm text-red-800 ring-1 ring-red-200">{{ $message }}</div>
    @enderror

    @unless ($canEdit)
        <div role="status" class="mb-6 rounded-xl bg-slate-100 p-4 text-sm text-slate-700 ring-1 ring-slate-200">
            You can view these settings but not change them.
        </div>
    @endunless

    @if (empty($groups))
        <x-ui.card><x-ui.empty-state title="No settings available" icon="settings" /></x-ui.card>
    @else
        <div x-data="{
                tab: null, tabs: @js($keys), start: @js($start),
                init() {
                    const h = decodeURIComponent(location.hash.slice(1));
                    this.tab = this.tabs.includes(this.start) ? this.start : (this.tabs.includes(h) ? h : this.tabs[0]);
                },
                go(t) { this.tab = t; history.replaceState(null, '', '#' + encodeURIComponent(t)); },
                move(dir) {
                    const i = this.tabs.indexOf(this.tab);
                    const n = this.tabs[(i + dir + this.tabs.length) % this.tabs.length];
                    this.go(n); this.$nextTick(() => document.getElementById('tab-' + n)?.focus());
                },
            }">

            @if (count($groups) > 1)
                <div class="mb-6 overflow-x-auto border-b border-slate-200">
                    <div role="tablist" aria-label="Settings groups" class="-mb-px flex gap-6">
                        @foreach ($groups as $key => $group)
                            <button type="button" role="tab" id="tab-{{ $key }}" aria-controls="panel-{{ $key }}"
                                    x-bind:aria-selected="tab === @js($key)" x-bind:tabindex="tab === @js($key) ? 0 : -1"
                                    x-on:click="go(@js($key))" x-on:keydown.arrow-right.prevent="move(1)" x-on:keydown.arrow-left.prevent="move(-1)"
                                    x-bind:class="tab === @js($key) ? 'border-brand-600 text-brand-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
                                    class="whitespace-nowrap border-b-2 px-1 pb-3 text-sm font-medium">{{ $group['label'] ?? \Illuminate\Support\Str::headline($key) }}</button>
                        @endforeach
                    </div>
                </div>
            @endif

            @foreach ($groups as $key => $group)
                <div id="panel-{{ $key }}" role="tabpanel" aria-labelledby="tab-{{ $key }}" x-show="tab === @js($key)" x-cloak>
                    @include('admin.settings._group', [
                        'groupKey'  => $key,
                        'group'     => $group,
                        'values'    => $values[$key] ?? [],
                        'canEdit'   => $canEdit,
                        'submitted' => $submitted,
                    ])
                </div>
            @endforeach
        </div>
    @endif
</x-layouts.app>