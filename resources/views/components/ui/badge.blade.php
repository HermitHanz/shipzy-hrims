@props(['variant' => 'gray'])
@php
    $variants = [
        'gray'   => 'bg-slate-100 text-slate-700',
        'green'  => 'bg-emerald-50 text-emerald-700',
        'yellow' => 'bg-amber-50 text-amber-700',
        'red'    => 'bg-red-50 text-red-700',
        'brand'  => 'bg-brand-50 text-brand-700',
    ];
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ' . ($variants[$variant] ?? $variants['gray'])]) }}>
    {{ $slot }}
</span>