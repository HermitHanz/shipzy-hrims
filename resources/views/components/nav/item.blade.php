@props(['item'])
@php
    $href   = Route::has($item['route']) ? route($item['route']) : '#';
    $active = request()->routeIs($item['active'] ?? $item['route']);
@endphp
<li>
    <a href="{{ $href }}"
       @class([
           'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
           'bg-brand-50 text-brand-700' => $active,
           'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => !$active,
       ])
       @if ($active) aria-current="page" @endif>
        <x-ui.icon :name="$item['icon'] ?? 'home'" @class(['size-5', 'text-brand-600' => $active, 'text-slate-400 group-hover:text-slate-600' => !$active]) />
        {{ $item['label'] }}
    </a>
</li>