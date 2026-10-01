{{-- $employee, $action --}}
@php
    $cls = 'block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-brand-500';
    $existing = $employee->emergencyContacts->map(fn ($c) => [
        'name' => $c->name, 'relationship' => $c->relationship, 'phone' => $c->phone,
        'alt_phone' => $c->alt_phone, 'address' => $c->address, 'is_primary' => (bool) $c->is_primary,
    ])->all();
    $initial = collect(old('contacts', $existing))->values()->map(fn ($c) => [
        'key' => \Illuminate\Support\Str::random(6),
        'name' => $c['name'] ?? '', 'relationship' => $c['relationship'] ?? '', 'phone' => $c['phone'] ?? '',
        'alt_phone' => $c['alt_phone'] ?? '', 'address' => $c['address'] ?? '',
        'is_primary' => filter_var($c['is_primary'] ?? false, FILTER_VALIDATE_BOOLEAN),
    ])->all();
@endphp
<x-ui.card title="Emergency contacts" x-data="{ editing: {{ $errors->has('contacts') || collect($errors->keys())->contains(fn ($k) => str_starts_with($k, 'contacts')) ? 'true' : 'false' }} }">
    <x-slot:actions>
        @can('updateEmergency', $employee)
            <x-ui.button variant="secondary" x-show="! editing" x-on:click="editing = true"><x-ui.icon name="pencil" class="size-4" /> Edit</x-ui.button>
        @endcan
    </x-slot:actions>

    <div x-show="! editing">
        @forelse ($employee->emergencyContacts as $c)
            <div class="border-b border-slate-100 py-3 first:pt-0 last:border-0 last:pb-0">
                <p class="flex flex-wrap items-center gap-2 text-sm font-medium">{{ $c->name }} <span class="font-normal text-slate-500">· {{ $c->relationship }}</span>
                    @if ($c->is_primary)<x-ui.badge variant="brand">Primary</x-ui.badge>@endif</p>
                <p class="mt-0.5 text-sm text-slate-600">{{ $c->phone }}@if ($c->alt_phone) · {{ $c->alt_phone }}@endif</p>
                @if ($c->address)<p class="text-xs text-slate-500">{{ $c->address }}</p>@endif
            </div>
        @empty
            <x-ui.empty-state title="No emergency contact yet" icon="users" />
        @endforelse
    </div>

    @can('updateEmergency', $employee)
        <form x-show="editing" x-cloak method="POST" action="{{ $action }}" class="space-y-4" x-data="emergencyContacts(@js($initial))">
            @csrf @method('PUT')

            @foreach ($errors->getMessages() as $key => $messages)
                @if (str_starts_with($key, 'contacts'))
                    <p class="rounded-lg bg-red-50 p-3 text-sm text-red-700" role="alert">{{ $messages[0] }}</p>
                @endif
            @endforeach

            <template x-for="(c, i) in contacts" :key="c.key">
                <fieldset class="rounded-xl p-4 ring-1 ring-slate-200">
                    <legend class="px-1 text-sm font-semibold" x-text="'Contact ' + (i + 1)"></legend>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label class="mb-1 block text-sm font-medium text-slate-700" x-bind:for="`ec-name-${c.key}`">Name</label>
                            <input type="text" required maxlength="150" class="{{ $cls }}" x-model="c.name" x-bind:id="`ec-name-${c.key}`" x-bind:name="`contacts[${i}][name]`"></div>
                        <div><label class="mb-1 block text-sm font-medium text-slate-700" x-bind:for="`ec-rel-${c.key}`">Relationship</label>
                            <input type="text" required maxlength="100" class="{{ $cls }}" x-model="c.relationship" x-bind:id="`ec-rel-${c.key}`" x-bind:name="`contacts[${i}][relationship]`"></div>
                        <div><label class="mb-1 block text-sm font-medium text-slate-700" x-bind:for="`ec-phone-${c.key}`">Phone</label>
                            <input type="tel" required maxlength="30" autocomplete="off" class="{{ $cls }}" x-model="c.phone" x-bind:id="`ec-phone-${c.key}`" x-bind:name="`contacts[${i}][phone]`"></div>
                        <div><label class="mb-1 block text-sm font-medium text-slate-700" x-bind:for="`ec-alt-${c.key}`">Alternate phone (optional)</label>
                            <input type="tel" maxlength="30" autocomplete="off" class="{{ $cls }}" x-model="c.alt_phone" x-bind:id="`ec-alt-${c.key}`" x-bind:name="`contacts[${i}][alt_phone]`"></div>
                        <div class="sm:col-span-2"><label class="mb-1 block text-sm font-medium text-slate-700" x-bind:for="`ec-addr-${c.key}`">Address (optional)</label>
                            <input type="text" maxlength="255" class="{{ $cls }}" x-model="c.address" x-bind:id="`ec-addr-${c.key}`" x-bind:name="`contacts[${i}][address]`"></div>
                    </div>
                    <div class="mt-3 flex items-center justify-between">
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="radio" name="_primary" class="size-4 border-slate-300 text-brand-600 focus:ring-brand-500" x-model.number="primary" x-bind:value="i"> Primary contact
                        </label>
                        <button type="button" class="text-sm font-medium text-red-600 hover:text-red-700" x-on:click="remove(i)">Remove</button>
                    </div>
                    <input type="hidden" x-bind:name="`contacts[${i}][is_primary]`" x-bind:value="primary === i ? 1 : 0">
                </fieldset>
            </template>

            <p x-show="contacts.length === 0" class="text-sm text-slate-500">No contacts. Saving now will clear the list.</p>

            <div class="flex flex-wrap items-center justify-between gap-2">
                <x-ui.button variant="secondary" x-show="contacts.length < 3" x-on:click="add()"><x-ui.icon name="plus" class="size-4" /> Add contact</x-ui.button>
                <span class="text-xs text-slate-500" x-text="contacts.length + ' of 3'"></span>
            </div>
            <div class="flex justify-end gap-2">
                <x-ui.button variant="secondary" x-on:click="editing = false">Cancel</x-ui.button>
                <x-ui.button type="submit">Save contacts</x-ui.button>
            </div>
        </form>
    @endcan
</x-ui.card>