{{-- Optional: $branch, $readOnly --}}
@php
    $editing  = isset($branch);
    $readOnly = $readOnly ?? false;
@endphp

<form method="POST" action="{{ $editing ? route('organization.branches.update', $branch) : route('organization.branches.store') }}" class="space-y-6">
    @csrf
    @if ($editing) @method('PUT') @endif

    <x-ui.card title="Details">
        <div class="grid gap-5 sm:grid-cols-2">
            @if ($editing)
                <div>
                    <p class="mb-1 text-sm font-medium text-slate-700">Code</p>
                    <code class="block rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700 ring-1 ring-inset ring-slate-200">{{ $branch->code }}</code>
                    <p class="mt-1 text-xs text-slate-500">The code can't be changed.</p>
                </div>
            @else
                <x-ui.input name="code" label="Code" placeholder="e.g. MNL-01" required maxlength="20"
                            hint="2–20 letters, numbers or hyphens. Saved in uppercase. Can't be changed later." />
            @endif

            <x-ui.input name="name" label="Name" :value="$branch->name ?? null" :disabled="$readOnly" required maxlength="150" />

            <div class="sm:col-span-2">
                <x-ui.textarea name="address" label="Address" :value="$branch->address ?? null" :disabled="$readOnly" maxlength="255" />
            </div>
        </div>
    </x-ui.card>

    <div class="flex justify-end gap-2">
        <x-ui.button variant="secondary" :href="route('organization.branches.index')">{{ $readOnly ? 'Back' : 'Cancel' }}</x-ui.button>
        @unless ($readOnly)
            <x-ui.button type="submit">{{ $editing ? 'Save changes' : 'Create branch' }}</x-ui.button>
        @endunless
    </div>
</form>
