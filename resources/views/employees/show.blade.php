@php
    $user = auth()->user();
    $tabs = array_filter([
        'overview'  => ['Overview', true],
        'personal'  => ['Personal', $user->can('viewPersonal', $employee)],
        'emergency' => ['Emergency contacts', $user->can('viewEmergency', $employee)],
        'ids'       => ['Government IDs', $user->can('viewGovernmentIds', $employee)],
        'bank'      => ['Bank accounts', $user->can('viewBank', $employee)],
        'login'     => ['Login', $user->can('update', $employee) || $user->can('create', \App\Models\User::class)],
    ], fn ($t) => $t[1]);

    // If validation failed, open the tab that holds the error
    $errorTabs = [
        'personal'  => ['personal_email', 'phone', 'birth_date', 'address_', 'barangay', 'city', 'province', 'postal_code'],
        'emergency' => ['contacts'],
        'ids'       => ['sss', 'tin', 'philhealth', 'pagibig'],
        'bank'      => ['bank_name', 'account_name', 'account_number', 'decision', 'reason', 'bank'],
        'login'     => ['email'],
        'overview'  => ['status', 'separation_'],
    ];
    $startTab = null;
    foreach ($errors->keys() as $key) {
        foreach ($errorTabs as $tab => $prefixes) {
            if (\Illuminate\Support\Str::startsWith($key, $prefixes)) { $startTab = $tab; break 2; }
        }
    }
    $fmt = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('M j, Y') : '—';
    $rows = [
        'Employee number'     => $employee->employee_number,
        'Work email'          => $employee->work_email,
        'Job title'           => $employee->job_title ?: '—',
        'Employment type'     => \Illuminate\Support\Str::headline((string) $employee->employment_type),
        'Department'          => $employee->department?->name ?? '—',
        'Branch'              => $employee->branch?->name ?? '—',
        'Manager'             => $employee->manager ? $employee->manager->last_name . ', ' . $employee->manager->first_name : '—',
        'Hire date'           => $fmt($employee->hire_date),
        'Regularization date' => $fmt($employee->regularization_date),
        'Onboarding'          => $employee->onboarding_completed_at ? 'Completed ' . $fmt($employee->onboarding_completed_at) : 'Pending',
        'Privacy notice'      => $employee->privacy_acknowledged_at ? 'Acknowledged ' . $fmt($employee->privacy_acknowledged_at) : 'Not yet acknowledged',
    ];
    if ($employee->status === 'separated') {
        $rows['Separation date']   = $fmt($employee->separation_date);
        $rows['Separation reason'] = $employee->separation_reason ?: '—';
    }
@endphp

<x-layouts.app :title="$employee->full_name">
    <x-slot:header>
        <x-ui.page-header :title="$employee->full_name" :subtitle="$employee->employee_number . ' · ' . ($employee->job_title ?: '—')">
            <x-status-badge :status="$employee->status" />
            @can('viewAny', \App\Models\Employee::class)
                <x-ui.button variant="secondary" :href="route('employees.index')">Back</x-ui.button>
            @endcan
            @can('update', $employee)
                <x-ui.button :href="route('employees.edit', $employee)"><x-ui.icon name="pencil" class="size-4" /> Edit</x-ui.button>
            @endcan
        </x-ui.page-header>
    </x-slot:header>

    @error('employee')
        <div role="alert" class="mb-6 rounded-xl bg-red-50 p-4 text-sm text-red-800 ring-1 ring-red-200">{{ $message }}</div>
    @enderror

    @if (session('prompt_login') && ! $employee->user)
        @can('create', \App\Models\User::class)
            <div role="status" class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-xl bg-brand-50 p-4 ring-1 ring-brand-100">
                <p class="text-sm text-brand-700"><strong>Next step:</strong> create a login so {{ $employee->first_name }} can sign in.</p>
                <x-ui.button x-on:click="$dispatch('go-tab', 'login')">Create login</x-ui.button>
            </div>
        @endcan
    @endif

    <div x-data="{
            tab: null, tabs: @js(array_keys($tabs)), start: @js($startTab),
            init() { const h = location.hash.slice(1); this.tab = this.start || (this.tabs.includes(h) ? h : 'overview'); },
            go(t) { if (this.tabs.includes(t)) { this.tab = t; history.replaceState(null, '', '#' + t); } },
            move(dir) {
                const i = this.tabs.indexOf(this.tab); const n = this.tabs[(i + dir + this.tabs.length) % this.tabs.length];
                this.go(n); this.$nextTick(() => document.getElementById('tab-' + n)?.focus());
            },
        }" x-on:go-tab.window="go($event.detail)">

        @if (count($tabs) > 1)
            <div class="mb-6 overflow-x-auto border-b border-slate-200">
                <div role="tablist" aria-label="Employee sections" class="-mb-px flex gap-6">
                    @foreach ($tabs as $key => [$label])
                        <button type="button" role="tab" id="tab-{{ $key }}" aria-controls="panel-{{ $key }}"
                                x-bind:aria-selected="tab === '{{ $key }}'" x-bind:tabindex="tab === '{{ $key }}' ? 0 : -1"
                                x-on:click="go('{{ $key }}')" x-on:keydown.arrow-right.prevent="move(1)" x-on:keydown.arrow-left.prevent="move(-1)"
                                x-bind:class="tab === '{{ $key }}' ? 'border-brand-600 text-brand-700' : 'border-transparent text-slate-500 hover:text-slate-700'"
                                class="whitespace-nowrap border-b-2 px-1 pb-3 text-sm font-medium">{{ $label }}</button>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Overview --}}
        <div id="panel-overview" role="tabpanel" aria-labelledby="tab-overview" x-show="tab === 'overview'" x-cloak>
            <x-ui.card title="Employee record">
                <x-slot:actions>
                    @can('changeStatus', $employee)
                        <x-ui.button variant="secondary" x-on:click="$dispatch('open-modal', 'employee-status')">Change status</x-ui.button>
                    @endcan
                </x-slot:actions>
                <dl class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($rows as $label => $value)
                        <div><dt class="text-slate-500">{{ $label }}</dt><dd class="mt-0.5 font-medium">{{ $value }}</dd></div>
                    @endforeach
                </dl>
            </x-ui.card>
        </div>

        @can('viewPersonal', $employee)
            <div id="panel-personal" role="tabpanel" aria-labelledby="tab-personal" x-show="tab === 'personal'" x-cloak>
                @include('employees.partials.personal', ['employee' => $employee, 'action' => route('employees.personal.update', $employee)])
            </div>
        @endcan

        @can('viewEmergency', $employee)
            <div id="panel-emergency" role="tabpanel" aria-labelledby="tab-emergency" x-show="tab === 'emergency'" x-cloak>
                @include('employees.partials.emergency', ['employee' => $employee, 'action' => route('employees.emergency.update', $employee)])
            </div>
        @endcan

        @can('viewGovernmentIds', $employee)
            <div id="panel-ids" role="tabpanel" aria-labelledby="tab-ids" x-show="tab === 'ids'" x-cloak>
                @include('employees.partials.government-ids', ['employee' => $employee, 'action' => route('employees.government-ids.update', $employee)])
            </div>
        @endcan

        @can('viewBank', $employee)
            <div id="panel-bank" role="tabpanel" aria-labelledby="tab-bank" x-show="tab === 'bank'" x-cloak>
                @include('employees.partials.bank', ['employee' => $employee, 'bankAccounts' => $bankAccounts, 'storeAction' => route('employees.bank-accounts.store', $employee), 'showReview' => true])
            </div>
        @endcan

        @if (isset($tabs['login']))
            <div id="panel-login" role="tabpanel" aria-labelledby="tab-login" x-show="tab === 'login'" x-cloak>
                @include('employees.partials.login', ['employee' => $employee])
            </div>
        @endif
    </div>

    {{-- Status modal --}}
    @can('changeStatus', $employee)
        <x-ui.modal name="employee-status" title="Change status" description="Choose the employee's new status.">
            <form method="POST" action="{{ route('employees.status.update', $employee) }}" class="space-y-4"
                  x-data="{ status: @js(old('status', $employee->status)) }">
                @csrf @method('PUT')
                <x-ui.select name="status" label="Status" x-model="status" data-autofocus>
                    @foreach ($statuses as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
                </x-ui.select>

                <div x-show="status === 'separated'" x-cloak class="space-y-4 rounded-lg bg-amber-50 p-4 ring-1 ring-amber-200">
                    <p class="text-sm text-amber-900">Separating this employee also <strong>deactivates their login</strong>. It's blocked while they have active direct reports or head a department.</p>
                    <x-ui.input name="separation_date" type="date" label="Separation date" />
                    <x-ui.textarea name="separation_reason" label="Reason" />
                </div>
                @if ($employee->status === 'separated')
                    <p x-show="status !== 'separated'" x-cloak class="text-sm text-slate-600">Reactivating does <strong>not</strong> re-enable their login. Do that from the user's access page.</p>
                @endif

                <div class="flex justify-end gap-2">
                    <x-ui.button variant="secondary" x-on:click="hide()">Cancel</x-ui.button>
                    <x-ui.button type="submit">Save status</x-ui.button>
                </div>
            </form>
        </x-ui.modal>

        @if ($errors->hasAny(['status', 'separation_date', 'separation_reason']))
            <div x-data x-init="$nextTick(() => $dispatch('open-modal', 'employee-status'))"></div>
        @endif
    @endcan
</x-layouts.app>