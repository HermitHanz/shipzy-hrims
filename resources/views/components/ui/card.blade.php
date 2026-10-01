@props(['title' => null])
<section {{ $attributes->merge(['class' => 'rounded-2xl bg-white shadow-sm ring-1 ring-slate-200']) }}>
    @if ($title || isset($actions))
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h2 class="text-base font-semibold">{{ $title }}</h2>
            {{ $actions ?? '' }}
        </div>
    @endif
    <div class="p-5">{{ $slot }}</div>
</section>