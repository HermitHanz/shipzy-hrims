@props(['name', 'title', 'description' => null])
<div x-data="modal"
     x-on:open-modal.window="$event.detail === '{{ $name }}' && show()"
     x-on:close-modal.window="$event.detail === '{{ $name }}' && hide()"
     x-on:keydown.escape.window="open && hide()"
     x-show="open" x-cloak
     class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
     role="dialog" aria-modal="true" aria-labelledby="modal-{{ $name }}-title">
    <div class="fixed inset-0 bg-slate-900/50" x-on:click="hide()" aria-hidden="true"></div>
    <div x-ref="panel" x-on:keydown.tab="trap($event)" class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        <h2 id="modal-{{ $name }}-title" class="text-lg font-semibold">{{ $title }}</h2>
        @if ($description)<p class="mt-2 text-sm text-slate-600">{{ $description }}</p>@endif
        <div class="mt-5">{{ $slot }}</div>
    </div>
</div>