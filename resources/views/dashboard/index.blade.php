<x-layouts.app title="Dashboard">
    <x-slot:header>
        <x-ui.page-header
            :title="'Welcome back, ' . (explode(' ', auth()->user()->name ?? 'there')[0]) . '!'"
            :subtitle="now()->format('l, F j, Y')">
            @foreach ($quickActions as $action)
                @if (empty($action['can']) || auth()->user()?->can($action['can']))
                    <x-ui.button
                        :variant="$loop->first ? 'primary' : 'secondary'"
                        :href="Route::has($action['route']) ? route($action['route']) : '#'">
                        @if ($loop->first)<x-ui.icon name="plus" class="size-4" />@endif
                        {{ $action['label'] }}
                    </x-ui.button>
                @endif
            @endforeach
        </x-ui.page-header>
    </x-slot:header>

    <x-profile-nudge :missing="$profileNudge ?? []" />

    <x-ui.card title="My shortcuts" class="mb-6">
        <div class="flex flex-wrap gap-2">
            @foreach ([
                ['My profile', 'profile.show'], ['My attendance', 'me.attendance'],
                ['Request leave', 'me.leave'], ['My payslips', 'me.payslips'],
            ] as [$label, $route])
                @if (Route::has($route))
                    <x-ui.button variant="secondary" :href="route($route)">{{ $label }}</x-ui.button>
                @endif
            @endforeach
            @if (! collect(['me.profile','me.attendance','me.leave','me.payslips'])->contains(fn ($r) => Route::has($r)))
                <p class="text-sm text-slate-500">Self-service pages will appear here as they're built.</p>
            @endif
        </div>
    </x-ui.card>

    {{-- Stats --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($stats as $stat)
            @if (auth()->user()->canAny($stat['can']))
                <x-ui.stat-card
                    :label="$stat['label']" :value="$stat['value']" :icon="$stat['icon']" :note="$stat['note']"
                    :href="Route::has($stat['href']) ? route($stat['href']) : '#'" />
            @endif
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-3">
        {{-- Attendance chart --}}
        @can('attendance.record.view-all')
            <x-ui.card title="Attendance this week" class="xl:col-span-2">
                <div class="flex h-48 items-end gap-3">
                    @foreach ($attendance as $bar)
                        <div class="flex h-full flex-1 flex-col items-center justify-end gap-2">
                            <span class="text-xs text-slate-500">{{ $bar['rate'] }}%</span>
                            <div class="w-full rounded-t-lg bg-brand-500/80 transition hover:bg-brand-600"
                                style="height: {{ max($bar['rate'], 2) }}%"
                                title="{{ $bar['day'] }}: {{ $bar['rate'] }}%"></div>
                            <span class="text-xs font-medium text-slate-600">{{ $bar['day'] }}</span>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>
        @endcan

        {{-- Pending approvals --}}
        @canany(['leave.request.approve-team','leave.request.approve-all'])
            <x-ui.card title="Pending approvals">
                <x-slot:actions>
                    <a href="#" class="text-sm font-medium text-brand-600 hover:text-brand-700">View all</a>
                </x-slot:actions>
                <ul class="divide-y divide-slate-100">
                    @forelse ($pending as $item)
                        <li class="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">{{ $item['name'] }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $item['type'] }} · {{ $item['date'] }}</p>
                            </div>
                            <x-ui.badge variant="yellow">Pending</x-ui.badge>
                        </li>
                    @empty
                        <li class="py-6 text-center text-sm text-slate-500">Nothing waiting on you 🎉</li>
                    @endforelse
                </ul>
            </x-ui.card>
        @endcan
    </div>

    {{-- Recent employees --}}
    @can('employee.record.view-all')
        <x-ui.card title="Recently added employees" class="mt-6">
            <x-slot:actions>
                <a href="{{ Route::has('admin.employees.index') ? route('admin.employees.index') : '#' }}"
                class="text-sm font-medium text-brand-600 hover:text-brand-700">View all</a>
            </x-slot:actions>
            <div class="-mx-5 -mb-5 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-y border-slate-100 bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Name</th>
                            <th class="px-5 py-3 font-medium">Department</th>
                            <th class="px-5 py-3 font-medium">Branch</th>
                            <th class="px-5 py-3 font-medium">Status</th>
                            <th class="px-5 py-3 font-medium">Joined</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($recentEmployees as $emp)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3 font-medium">{{ $emp['name'] }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $emp['department'] }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $emp['branch'] }}</td>
                                <td class="px-5 py-3">
                                    <x-ui.badge :variant="$emp['status'] === 'Active' ? 'green' : 'yellow'">{{ $emp['status'] }}</x-ui.badge>
                                </td>
                                <td class="px-5 py-3 text-slate-600">{{ $emp['joined'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    @endcan
</x-layouts.app>