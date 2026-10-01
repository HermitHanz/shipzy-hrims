@props(['item'])
@php
    $active = request()->routeIs(...(array) ($item['active'] ?? $item['route']));
@endphp
<li>
    <a href="{{ route($item['route']) }}"
       @class([
           'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
           'bg-brand-50 text-brand-700' => $active,
           'text-slate-600 hover:bg-slate-100 hover:text-slate-900' => ! $active,
       ])
       @if ($active) aria-current="page" @endif>
        <x-ui.icon :name="$item['icon'] ?? 'home'" :class="$active ? 'size-5 text-brand-600' : 'size-5 text-slate-400 group-hover:text-slate-600'" />
        {{ $item['label'] }}
    </a>
</li>