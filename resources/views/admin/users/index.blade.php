@php $statusVariant = ['active' => 'green', 'inactive' => 'gray', 'suspended' => 'red']; @endphp
<x-layouts.app title="Users">
    <x-slot:header>
        <x-ui.page-header title="Users" subtitle="Login accounts and what each person can access.">
            @can('create', \App\Models\User::class)
                <x-ui.button :href="route('admin.users.create')"><x-ui.icon name="plus" class="size-4" /> New user</x-ui.button>
            @endcan
        </x-ui.page-header>
    </x-slot:header>

    <x-ui.card>
        <form method="GET" role="search" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[1fr_12rem_12rem_auto]">
            <x-ui.input name="q" label="Search" placeholder="Name or email" :value="request('q')" />
            <x-ui.select name="role" label="Role" placeholder="All roles">
                @foreach ($roles as $r)
                    <option value="{{ $r->name }}" @selected(request('role') === $r->name)>{{ $r->label ?: $r->name }}</option>
                @endforeach
            </x-ui.select>
            <x-ui.select name="status" label="Status" placeholder="Any status">
                @foreach ($statusVariant as $s => $v)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </x-ui.select>
            <div class="flex items-end gap-2">
                <x-ui.button type="submit">Filter</x-ui.button>
                @if (request()->hasAny(['q', 'role', 'status']))
                    <x-ui.button variant="secondary" :href="route('admin.users.index')">Reset</x-ui.button>
                @endif
            </div>
        </form>
    </x-ui.card>

    <x-ui.card class="mt-6">
        <div class="-mx-5 -my-5">
            @if ($users->isEmpty())
                <x-ui.empty-state title="No users found" description="Try changing your filters, or create a new account.">
                    @can('create', \App\Models\User::class)
                        <x-ui.button :href="route('admin.users.create')">New user</x-ui.button>
                    @endcan
                </x-ui.empty-state>
            @else
                <x-ui.table>
                    <x-slot:head>
                        @foreach (['Name', 'Email', 'Employee', 'Roles', 'Status', 'Last login', ''] as $h)
                            <th scope="col" class="px-5 py-3 font-medium">{{ $h ?: 'Actions' }}</th>
                        @endforeach
                    </x-slot:head>

                    @foreach ($users as $u)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <span aria-hidden="true" class="grid size-8 place-items-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700">{{ strtoupper(mb_substr($u->name, 0, 1)) }}</span>
                                    <span class="font-medium">{{ $u->name }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-slate-600">{{ $u->email }}</td>
                            <td class="px-5 py-3 text-slate-600">
                                @if ($u->employee)
                                    {{ $u->employee->first_name }} {{ $u->employee->last_name }}
                                    <span class="block text-xs text-slate-400">{{ $u->employee->employee_number }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($u->roles as $role)
                                        <x-ui.badge :variant="$role->is_system ? 'brand' : 'gray'">{{ $role->label ?: $role->name }}</x-ui.badge>
                                    @empty
                                        <span class="text-slate-400">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-5 py-3"><x-ui.badge :variant="$statusVariant[$u->status] ?? 'gray'">{{ ucfirst($u->status) }}</x-ui.badge></td>
                            <td class="whitespace-nowrap px-5 py-3 text-slate-600">
                                {{ $u->last_login_at ? \Illuminate\Support\Carbon::parse($u->last_login_at)->diffForHumans() : 'Never' }}
                            </td>
                            <td class="px-5 py-3">
                                @can('view', $u)
                                    <x-ui.button variant="secondary" :href="route('admin.users.access', $u)" class="!px-3 !py-1.5">
                                        <x-ui.icon name="{{ auth()->user()->can('manageAccess', $u) ? 'key' : 'eye' }}" class="size-4" />
                                        {{ auth()->user()->can('manageAccess', $u) ? 'Manage access' : 'View' }}
                                    </x-ui.button>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </x-ui.table>

                @if ($users->hasPages())
                    <div class="border-t border-slate-100 px-5 py-3">{{ $users->links() }}</div>
                @endif
            @endif
        </div>
    </x-ui.card>
</x-layouts.app>