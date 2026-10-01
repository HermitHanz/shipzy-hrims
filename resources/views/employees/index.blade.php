<x-layouts.app title="Employees">
    <x-slot:header>
        <x-ui.page-header title="Employees" subtitle="Everyone in your scope.">
            @can('create', \App\Models\Employee::class)
                <x-ui.button :href="route('employees.create')"><x-ui.icon name="plus" class="size-4" /> New employee</x-ui.button>
            @endcan
        </x-ui.page-header>
    </x-slot:header>

    <x-ui.card>
        <form method="GET" role="search" class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                <div class="lg:col-span-2"><x-ui.input name="q" label="Search" placeholder="Name, employee number or work email" :value="request('q')" /></div>
                <x-ui.select name="branch" label="Branch" placeholder="All branches">
                    @foreach ($branches as $b)<option value="{{ $b->id }}" @selected((string) request('branch') === (string) $b->id)>{{ $b->name }}</option>@endforeach
                </x-ui.select>
                <x-ui.select name="department" label="Department" placeholder="All departments">
                    @foreach ($departments as $d)<option value="{{ $d->id }}" @selected((string) request('department') === (string) $d->id)>{{ $d->name }}</option>@endforeach
                </x-ui.select>
                <x-ui.select name="status" label="Status" placeholder="Any status">
                    @foreach ($statuses as $v => $l)<option value="{{ $v }}" @selected(request('status') === $v)>{{ $l }}</option>@endforeach
                </x-ui.select>
            </div>
            <div class="flex flex-wrap items-end gap-4">
                <div class="w-full sm:w-56">
                    <x-ui.select name="employment_type" label="Employment type" placeholder="Any type">
                        @foreach ($types as $v => $l)<option value="{{ $v }}" @selected(request('employment_type') === $v)>{{ $l }}</option>@endforeach
                    </x-ui.select>
                </div>
                <label class="flex items-center gap-2 pb-2 text-sm text-slate-700">
                    <input type="checkbox" name="incomplete" value="1" @checked(request()->boolean('incomplete')) class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    Profile incomplete (onboarding pending)
                </label>
                <label class="flex items-center gap-2 pb-2 text-sm text-slate-700">
                    <input type="checkbox" name="no_login" value="1" @checked(request()->boolean('no_login')) class="size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    Has no login
                </label>
                <div class="flex gap-2 pb-0.5">
                    <x-ui.button type="submit">Filter</x-ui.button>
                    @if (request()->hasAny(['q', 'branch', 'department', 'status', 'employment_type', 'incomplete', 'no_login']))
                        <x-ui.button variant="secondary" :href="route('employees.index')">Reset</x-ui.button>
                    @endif
                </div>
            </div>
        </form>
    </x-ui.card>

    <x-ui.card class="mt-6">
        <div class="-mx-5 -my-5">
            @if ($employees->isEmpty())
                <x-ui.empty-state title="No employees found" description="Try changing your filters." icon="users">
                    @can('create', \App\Models\Employee::class)<x-ui.button :href="route('employees.create')">New employee</x-ui.button>@endcan
                </x-ui.empty-state>
            @else
                <x-ui.table>
                    <x-slot:head>
                        @foreach (['No.', 'Name', 'Job title', 'Department', 'Branch', 'Manager', 'Status', 'Hired', 'Login', 'Onboarding', 'Actions'] as $h)
                            <th scope="col" class="whitespace-nowrap px-4 py-3 font-medium">{{ $h }}</th>
                        @endforeach
                    </x-slot:head>
                    @foreach ($employees as $e)
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-600">{{ $e->employee_number }}</td>
                            <td class="px-4 py-3 font-medium">{{ $e->last_name }}, {{ $e->first_name }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $e->job_title ?: '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $e->department?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $e->branch?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $e->manager ? $e->manager->last_name . ', ' . $e->manager->first_name : '—' }}</td>
                            <td class="px-4 py-3"><x-status-badge :status="$e->status" /></td>
                            <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $e->hire_date ? \Illuminate\Support\Carbon::parse($e->hire_date)->format('M j, Y') : '—' }}</td>
                            <td class="px-4 py-3">
                                @if ($e->user)
                                    <x-ui.badge :variant="$e->user->status === 'active' ? 'green' : 'gray'">{{ $e->user->status === 'active' ? 'Has login' : 'Login ' . $e->user->status }}</x-ui.badge>
                                @else
                                    <x-ui.badge>No login</x-ui.badge>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($e->onboarding_completed_at)
                                    <span class="text-emerald-600" title="Onboarding complete"><x-ui.icon name="check-circle" class="size-5" /><span class="sr-only">Complete</span></span>
                                @else
                                    <x-ui.badge variant="yellow">Pending</x-ui.badge>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    @can('view', $e)
                                        <x-ui.button variant="secondary" :href="route('employees.show', $e)" class="!px-3 !py-1.5"><x-ui.icon name="eye" class="size-4" /> View</x-ui.button>
                                    @endcan
                                    @can('update', $e)
                                        <x-ui.button variant="secondary" :href="route('employees.edit', $e)" class="!px-3 !py-1.5"><x-ui.icon name="pencil" class="size-4" /> Edit</x-ui.button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>
                @if ($employees->hasPages())<div class="border-t border-slate-100 px-5 py-3">{{ $employees->links() }}</div>@endif
            @endif
        </div>
    </x-ui.card>
</x-layouts.app>