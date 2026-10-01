{{-- $employee, $action --}}
@php
    $d = $employee->personalDetail;
    $fields = ['personal_email', 'phone', 'birth_date', 'address_line1', 'address_line2', 'barangay', 'city', 'province', 'postal_code'];
    $address = collect([$d?->address_line1, $d?->address_line2, $d?->barangay, $d?->city, $d?->province, $d?->postal_code])->filter()->implode(', ');
@endphp
<x-ui.card title="Personal details" x-data="{ editing: {{ $errors->hasAny($fields) ? 'true' : 'false' }} }">
    <x-slot:actions>
        @can('updatePersonal', $employee)
            <x-ui.button variant="secondary" x-show="! editing" x-on:click="editing = true"><x-ui.icon name="pencil" class="size-4" /> Edit</x-ui.button>
        @endcan
    </x-slot:actions>

    <dl x-show="! editing" class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2">
        <div><dt class="text-slate-500">Personal email</dt><dd class="mt-0.5 font-medium">{{ $d?->personal_email ?: '—' }}</dd></div>
        <div><dt class="text-slate-500">Mobile number</dt><dd class="mt-0.5 font-medium">{{ $employee->phone ?: '—' }}</dd></div>
        <div><dt class="text-slate-500">Birthday</dt><dd class="mt-0.5 font-medium">{{ $d?->birth_date ? \Illuminate\Support\Carbon::parse($d->birth_date)->format('F j, Y') : '—' }}</dd></div>
        <div class="sm:col-span-2"><dt class="text-slate-500">Address</dt><dd class="mt-0.5 font-medium">{{ $address ?: '—' }}</dd></div>
    </dl>

    @can('updatePersonal', $employee)
        <form x-show="editing" x-cloak method="POST" action="{{ $action }}" class="space-y-5">
            @csrf @method('PUT')
            @include('employees.partials.personal-fields', ['detail' => $d, 'phone' => $employee->phone, 'required' => false])
            <div class="flex justify-end gap-2">
                <x-ui.button variant="secondary" x-on:click="editing = false">Cancel</x-ui.button>
                <x-ui.button type="submit">Save personal details</x-ui.button>
            </div>
        </form>
    @endcan
</x-ui.card>