@php
    $user = auth()->user();
    $groups = collect(config('hrims_navigation', []))
        ->map(function ($group) use ($user) {
            $group['items'] = collect($group['items'])->filter(function ($item) use ($user) {
                if (! \Illuminate\Support\Facades\Route::has($item['route'])) return false;
                $perms = $item['permissions'] ?? [];
                return empty($perms) || ($user && $user->canAny($perms));
            })->values();
            return $group;
        })
        ->filter(fn ($group) => $group['items']->isNotEmpty());
@endphp
<div class="flex h-full flex-col">
    <div class="flex h-16 shrink-0 items-center gap-3 border-b border-slate-200 px-6">
        <span class="grid size-8 place-items-center rounded-lg bg-brand-600 text-sm font-bold text-white">S</span>
        <p class="text-sm font-semibold">{{ config('app.name') }}</p>
    </div>

    <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-4" aria-label="Main">
        @foreach ($groups as $group)
            <div>
                @if (! empty($group['heading']))
                    <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">{{ $group['heading'] }}</p>
                @endif
                <ul class="space-y-1">
                    @foreach ($group['items'] as $item)
                        <x-sidebar-item :item="$item" />
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>
</div>