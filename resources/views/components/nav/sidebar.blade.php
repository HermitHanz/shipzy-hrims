@props(['items' => [], 'portal' => ''])
<div class="flex h-full flex-col">
    <div class="flex h-16 shrink-0 items-center gap-3 border-b border-slate-200 px-6">
        <span class="grid size-8 place-items-center rounded-lg bg-brand-600 text-sm font-bold text-white">S</span>
        <div class="leading-tight">
            <p class="text-sm font-semibold">{{ config('app.name') }}</p>
            <p class="text-xs text-slate-500">{{ $portal }}</p>
        </div>
    </div>

    <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-4">
        @foreach ($items as $group)
            <div>
                @if (!empty($group['heading']))
                    <p class="mb-2 px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">{{ $group['heading'] }}</p>
                @endif
                <ul class="space-y-1">
                    @foreach ($group['items'] as $item)
                        @if (empty($item['can']) || auth()->user()?->can($item['can']))
                            <x-nav.item :item="$item" />
                        @endif
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>
</div>