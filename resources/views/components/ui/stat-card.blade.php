@props(['label', 'value', 'icon' => 'users', 'note' => null, 'href' => '#'])
<a href="{{ $href }}" class="group block rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition hover:ring-brand-500/50">
    <div class="flex items-center justify-between">
        <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
        <span class="grid size-9 place-items-center rounded-lg bg-brand-50 text-brand-600">
            <x-ui.icon :name="$icon" class="size-5" />
        </span>
    </div>
    <p class="mt-3 text-3xl font-semibold tracking-tight">{{ $value }}</p>
    @if ($note)
        <p class="mt-1 text-xs text-slate-500">{{ $note }}</p>
    @endif
</a>