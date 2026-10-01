@props(['label' => null, 'masked', 'field', 'url', 'accountId' => null, 'canReveal' => false, 'compact' => false])
<div x-data="maskedField(@js(['url' => $url, 'field' => $field, 'accountId' => $accountId]))"
     x-on:visibilitychange.document="if (document.hidden) hide()"
     {{ $attributes->merge(['class' => 'flex flex-wrap items-center justify-between gap-3']) }}>
    <div class="min-w-0">
        @if (! $compact && $label)<p class="text-sm text-slate-500">{{ $label }}</p>@endif
        <p class="{{ $compact ? '' : 'mt-0.5 ' }}font-mono text-sm font-medium">
            <span x-show="! revealed">{{ $masked }}</span>
            <template x-if="revealed"><span class="select-all" x-text="value"></span></template>
        </p>
        <p class="mt-0.5 text-xs text-slate-500" x-show="revealed" x-cloak>Hides again in <span x-text="left"></span>s</p>
        <p class="mt-0.5 text-xs text-red-600" x-show="error" x-text="error" x-cloak role="alert"></p>
        <span class="sr-only" aria-live="polite" x-text="revealed ? 'Value revealed' : ''"></span>
    </div>
    @if ($canReveal)
        <x-ui.button variant="secondary" class="!px-3 !py-1.5" x-on:click="revealed ? hide() : reveal()" x-bind:disabled="busy">
            <x-ui.icon name="eye" class="size-4" /> <span x-text="revealed ? 'Hide' : 'Reveal'">Reveal</span>
        </x-ui.button>
    @endif
</div>