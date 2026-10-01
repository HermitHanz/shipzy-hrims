<x-layouts.app title="My profile">
    <x-slot:header><x-ui.page-header title="My profile" subtitle="Keep your details up to date." /></x-slot:header>

    @if (! $employee)
        <x-ui.card>
            <x-ui.empty-state title="No employee profile linked" description="Your account isn't linked to an employee record, so there's nothing to show here." icon="user" />
        </x-ui.card>
    @else
        <x-profile-nudge :missing="$profileNudge ?? []" :link="false" />

        <x-ui.card title="{{ $employee->full_name }}" class="mb-6">
            <dl class="grid gap-x-6 gap-y-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                <div><dt class="text-slate-500">Employee number</dt><dd class="mt-0.5 font-mono font-medium">{{ $employee->employee_number }}</dd></div>
                <div><dt class="text-slate-500">Job title</dt><dd class="mt-0.5 font-medium">{{ $employee->job_title ?: '—' }}</dd></div>
                <div><dt class="text-slate-500">Department</dt><dd class="mt-0.5 font-medium">{{ $employee->department?->name ?? '—' }}</dd></div>
                <div><dt class="text-slate-500">Branch</dt><dd class="mt-0.5 font-medium">{{ $employee->branch?->name ?? '—' }}</dd></div>
            </dl>
        </x-ui.card>

        <div class="space-y-6">
            @can('viewPersonal', $employee)
                <section id="personal" class="scroll-mt-20">@include('employees.partials.personal', ['employee' => $employee, 'action' => route('profile.personal.update')])</section>
            @endcan
            @can('viewEmergency', $employee)
                <section id="emergency" class="scroll-mt-20">@include('employees.partials.emergency', ['employee' => $employee, 'action' => route('profile.emergency.update')])</section>
            @endcan
            @can('viewGovernmentIds', $employee)
                <section id="ids" class="scroll-mt-20">@include('employees.partials.government-ids', ['employee' => $employee, 'action' => route('profile.government-ids.update')])</section>
            @endcan
            @can('viewBank', $employee)
                <section id="bank" class="scroll-mt-20">@include('employees.partials.bank', ['employee' => $employee, 'bankAccounts' => $bankAccounts, 'storeAction' => route('profile.bank-account.store'), 'showReview' => false])</section>
            @endcan
        </div>
    @endif
</x-layouts.app>