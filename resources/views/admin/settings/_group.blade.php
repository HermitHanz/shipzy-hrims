{{-- $groupKey, $group, $values, $canEdit, $submitted --}}
@php
    $isSubmitted = $submitted === $groupKey;
    $label       = $group['label'] ?? \Illuminate\Support\Str::headline($groupKey);
@endphp
<x-ui.card :title="$label">
    @if (! empty($group['description']))
        <p class="mb-5 text-sm text-slate-500">{{ $group['description'] }}</p>
    @endif

    <form method="POST" action="{{ route('admin.settings.update', $groupKey) }}">
        @csrf @method('PUT')
        <input type="hidden" name="_group" value="{{ $groupKey }}">

        <div class="max-w-2xl space-y-6">
            @foreach (($group['fields'] ?? []) as $name => $field)
                @include('admin.settings._field', compact('groupKey', 'name', 'field', 'values', 'canEdit', 'isSubmitted'))
            @endforeach
        </div>

        @if ($canEdit)
            <div class="mt-6 flex justify-end">
                <x-ui.button type="submit">Save {{ strtolower($label) }}</x-ui.button>
            </div>
        @endif
    </form>
</x-ui.card>