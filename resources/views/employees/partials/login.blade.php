{{-- $employee --}}
@php $account = $employee->user; @endphp
<x-ui.card title="Login account">
    @if ($account)
        <dl class="grid gap-4 text-sm sm:grid-cols-3">
            <div><dt class="text-slate-500">Login email</dt><dd class="mt-0.5 font-medium">{{ $account->email }}</dd></div>
            <div><dt class="text-slate-500">Account status</dt><dd class="mt-0.5"><x-status-badge type="account" :status="$account->status" /></dd></div>
            <div><dt class="text-slate-500">Last login</dt><dd class="mt-0.5 font-medium">{{ $account->last_login_at ? $account->last_login_at->diffForHumans() : 'Never' }}</dd></div>
        </dl>
        @if ($employee->status !== 'separated' && $account->status !== 'active')
            <p class="mt-4 rounded-lg bg-amber-50 p-3 text-sm text-amber-900 ring-1 ring-amber-200">This login is {{ $account->status }}. Changing the employee's status doesn't re-enable it.</p>
        @endif
        @can('view', $account)
            @if (Route::has('admin.users.access'))
                <div class="mt-4"><x-ui.button variant="secondary" :href="route('admin.users.access', $account)"><x-ui.icon name="key" class="size-4" /> Manage access</x-ui.button></div>
            @endif
        @endcan
    @else
        <p class="text-sm text-slate-600">{{ $employee->first_name }} doesn't have a login yet.</p>
        @can('create', \App\Models\User::class)
            <form method="POST" action="{{ route('employees.login.store', $employee) }}" class="mt-4 max-w-md space-y-4">
                @csrf
                <x-ui.input name="email" type="email" label="Login email" :value="$employee->work_email" hint="Defaults to the work email. A temporary password is shown once after creating." />
                <x-ui.button type="submit"><x-ui.icon name="key" class="size-4" /> Create login</x-ui.button>
            </form>
        @else
            <p class="mt-2 text-sm text-slate-500">You don't have permission to create logins.</p>
        @endcan
    @endif
</x-ui.card>