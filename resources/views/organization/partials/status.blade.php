{{-- $active (bool), $action (status URL), $noun ('branch' | 'department'), $blockReason (?string), $deactivateNote (string) --}}
@php
    $label = $active ? 'Deactivate' : 'Reactivate';
@endphp
<x-ui.card title="Status">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <p class="text-sm text-slate-600">Currently <x-ui.badge :variant="$active ? 'green' : 'gray'">{{ $active ? 'Active' : 'Inactive' }}</x-ui.badge></p>
        @if ($blockReason)
            <button type="button" disabled aria-disabled="true" title="{{ $blockReason }}"
                    class="inline-flex cursor-not-allowed items-center gap-1 rounded-lg px-4 py-2 text-sm font-medium text-slate-400 ring-1 ring-inset ring-slate-200">
                <span class="sr-only">{{ $label }} unavailable: {{ $blockReason }}</span><span aria-hidden="true">{{ $label }}</span>
            </button>
        @else
            <x-ui.button :variant="$active ? 'danger' : 'secondary'" x-on:click="$dispatch('open-modal', 'set-status')">{{ $label }}</x-ui.button>
        @endif
    </div>
    @if ($blockReason)<p class="mt-3 text-xs text-slate-500">{{ $blockReason }}</p>@endif
    @error('is_active')<p class="mt-3 text-sm text-red-600" role="alert">{{ $message }}</p>@enderror
</x-ui.card>

@unless ($blockReason)
    <x-ui.modal name="set-status" :title="$label . ' this ' . $noun . '?'"
                :description="$active ? $deactivateNote : 'It becomes available again for new employee records.'">
        <form method="POST" action="{{ $action }}" class="flex justify-end gap-2">
            @csrf @method('PATCH')
            <input type="hidden" name="is_active" value="{{ $active ? 0 : 1 }}">
            <x-ui.button variant="secondary" data-autofocus x-on:click="hide()">Cancel</x-ui.button>
            <x-ui.button type="submit" :variant="$active ? 'danger' : 'primary'">{{ $label }}</x-ui.button>
        </form>
    </x-ui.modal>
@endunless
