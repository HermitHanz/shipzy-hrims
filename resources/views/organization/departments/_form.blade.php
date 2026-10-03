{{-- $parents, $heads (collections). Optional: $department, $readOnly --}}
@php
    $editing  = isset($department);
    $readOnly = $readOnly ?? false;
@endphp

<form method="POST" action="{{ $editing ? route('organization.departments.update', $department) : route('organization.departments.store') }}" class="space-y-6">
    @csrf
    @if ($editing) @method('PUT') @endif

    <x-ui.card title="Details">
        <div class="grid gap-5 sm:grid-cols-2">
            @if ($editing)
                <div>
                    <p class="mb-1 text-sm font-medium text-slate-700">Code</p>
                    <code class="block rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700 ring-1 ring-inset ring-slate-200">{{ $department->code }}</code>
                    <p class="mt-1 text-xs text-slate-500">The code can't be changed.</p>
                </div>
            @else
                <x-ui.input name="code" label="Code" placeholder="e.g. HR" required maxlength="20"
                            hint="2–20 letters, numbers or hyphens. Saved in uppercase. Can't be changed later." />
            @endif

            <x-ui.input name="name" label="Name" :value="$department->name ?? null" :disabled="$readOnly" required maxlength="150" />

            <x-ui.select name="parent_id" label="Parent department" placeholder="None (top level)" :disabled="$readOnly"
                         hint="Only active departments can be picked.">
                @foreach ($parents as $p)
                    <option value="{{ $p->id }}" @selected((string) old('parent_id', $department->parent_id ?? '') === (string) $p->id)>{{ $p->name }} ({{ $p->code }}){{ $p->is_active ? '' : ' (inactive)' }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.select name="head_employee_id" label="Department head" placeholder="No head" :disabled="$readOnly">
                @foreach ($heads as $e)
                    <option value="{{ $e->id }}" @selected((string) old('head_employee_id', $department->head_employee_id ?? '') === (string) $e->id)>{{ $e->last_name }}, {{ $e->first_name }} ({{ $e->employee_number }})</option>
                @endforeach
            </x-ui.select>
        </div>
    </x-ui.card>

    <div class="flex justify-end gap-2">
        <x-ui.button variant="secondary" :href="route('organization.departments.index')">{{ $readOnly ? 'Back' : 'Cancel' }}</x-ui.button>
        @unless ($readOnly)
            <x-ui.button type="submit">{{ $editing ? 'Save changes' : 'Create department' }}</x-ui.button>
        @endunless
    </div>
</form>
