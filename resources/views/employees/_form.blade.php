{{-- $branches, $departments, $managers, $types. Optional: $employee (edit) --}}
@php
    $editing   = isset($employee);
    $placement = ! $editing || auth()->user()->can('reassign', $employee);
    $readonlyBox = 'rounded-lg bg-slate-50 px-3 py-2 text-sm ring-1 ring-inset ring-slate-200';
@endphp
<form method="POST" action="{{ $editing ? route('employees.update', $employee) : route('employees.store') }}" class="max-w-3xl space-y-6">
    @csrf
    @if ($editing) @method('PUT') @endif

    <x-ui.card title="Employee">
        <div class="grid gap-5 sm:grid-cols-2">
            @if ($editing)
                <div>
                    <p class="mb-1 text-sm font-medium text-slate-700">Employee number</p>
                    <p class="font-mono {{ $readonlyBox }}">{{ $employee->employee_number }}</p>
                    <p class="mt-1 text-xs text-slate-500">The employee number can't be changed.</p>
                </div>
            @else
                <x-ui.input name="employee_number" label="Employee number" required />
            @endif
            <x-ui.input name="work_email" type="email" label="Work email" :value="$employee->work_email ?? null" required autocomplete="off" />
            <x-ui.input name="first_name" label="First name" :value="$employee->first_name ?? null" required />
            <x-ui.input name="middle_name" label="Middle name" :value="$employee->middle_name ?? null" />
            <x-ui.input name="last_name" label="Last name" :value="$employee->last_name ?? null" required />
            <x-ui.input name="job_title" label="Job title" :value="$employee->job_title ?? null" required />
            <x-ui.select name="employment_type" label="Employment type" placeholder="Choose…" required>
                @foreach ($types as $v => $l)<option value="{{ $v }}" @selected(old('employment_type', $employee->employment_type ?? '') === $v)>{{ $l }}</option>@endforeach
            </x-ui.select>
            <div></div>
            <x-ui.input name="hire_date" type="date" label="Hire date" :value="isset($employee) && $employee->hire_date ? \Illuminate\Support\Carbon::parse($employee->hire_date)->format('Y-m-d') : null" required />
            <x-ui.input name="regularization_date" type="date" label="Regularization date" :value="isset($employee) && $employee->regularization_date ? \Illuminate\Support\Carbon::parse($employee->regularization_date)->format('Y-m-d') : null" />
        </div>
    </x-ui.card>

    <x-ui.card title="Placement">
        <div class="grid gap-5 sm:grid-cols-3">
            @if ($placement)
                <x-ui.select name="branch_id" label="Branch" placeholder="Choose…" required>
                    @foreach ($branches as $b)<option value="{{ $b->id }}" @selected((string) old('branch_id', $employee->branch_id ?? '') === (string) $b->id)>{{ $b->name }}</option>@endforeach
                </x-ui.select>
                <x-ui.select name="department_id" label="Department" placeholder="Choose…" required>
                    @foreach ($departments as $d)<option value="{{ $d->id }}" @selected((string) old('department_id', $employee->department_id ?? '') === (string) $d->id)>{{ $d->name }}</option>@endforeach
                </x-ui.select>
                <x-ui.select name="manager_id" label="Manager" placeholder="— None —">
                    @foreach ($managers as $m)
                        <option value="{{ $m->id }}" @selected((string) old('manager_id', $employee->manager_id ?? '') === (string) $m->id)>{{ $m->last_name }}, {{ $m->first_name }} ({{ $m->employee_number }})</option>
                    @endforeach
                </x-ui.select>
            @else
                @foreach ([['Branch', $employee->branch?->name], ['Department', $employee->department?->name], ['Manager', $employee->manager ? $employee->manager->last_name . ', ' . $employee->manager->first_name : null]] as [$label, $value])
                    <div>
                        <p class="mb-1 text-sm font-medium text-slate-700">{{ $label }}</p>
                        <p class="{{ $readonlyBox }}">{{ $value ?: '—' }}</p>
                        <p class="mt-1 text-xs text-slate-500">Requires reassign permission.</p>
                    </div>
                @endforeach
            @endif
        </div>
    </x-ui.card>

    @unless ($editing)
        @can('employee.personal.edit')
            <x-ui.card title="Personal details (optional)">
                @include('employees.partials.personal-fields', ['detail' => null, 'phone' => null, 'required' => false])
            </x-ui.card>
        @endcan
    @endunless

    <div class="flex justify-end gap-2">
        <x-ui.button variant="secondary" :href="$editing ? route('employees.show', $employee) : route('employees.index')">Cancel</x-ui.button>
        <x-ui.button type="submit">{{ $editing ? 'Save changes' : 'Create employee' }}</x-ui.button>
    </div>
</form>