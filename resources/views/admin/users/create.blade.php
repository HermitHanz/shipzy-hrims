<x-layouts.app title="New user">
    <x-slot:header>
        <x-ui.page-header title="New user" subtitle="Create a login account. A temporary password is generated for you." />
    </x-slot:header>

    <form method="POST" action="{{ route('admin.users.store') }}" class="max-w-2xl space-y-6">
        @csrf
        <x-ui.card title="Account">
            <div class="space-y-5">
                <x-ui.input name="name" label="Full name" required autocomplete="off" />
                <x-ui.input name="email" type="email" label="Email" required autocomplete="off" />
                <x-ui.select name="employee_id" label="Linked employee (optional)" placeholder="— None —"
                             hint="Only employees who don't have a login yet are listed.">
                    @foreach ($employees as $e)
                        <option value="{{ $e->id }}" @selected((string) old('employee_id') === (string) $e->id)>
                            {{ $e->last_name }}, {{ $e->first_name }} ({{ $e->employee_number }})
                        </option>
                    @endforeach
                </x-ui.select>
            </div>
        </x-ui.card>

        <x-ui.card title="Roles">
            <fieldset>
                <legend class="mb-3 text-sm text-slate-500">Select at least one. Only roles you're allowed to assign are listed. Direct permissions can be added afterwards.</legend>
                @include('admin.users._roles-list', ['roles' => $roles, 'selected' => old('roles', []), 'readonly' => false])
                @foreach (['roles', 'role', 'roles.*'] as $key)
                    @error($key)<p class="mt-2 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
                @endforeach
            </fieldset>
        </x-ui.card>

        <div class="flex justify-end gap-2">
            <x-ui.button variant="secondary" :href="route('admin.users.index')">Cancel</x-ui.button>
            <x-ui.button type="submit">Create account</x-ui.button>
        </div>
    </form>
</x-layouts.app>