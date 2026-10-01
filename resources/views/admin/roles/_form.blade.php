{{-- $levels (collection). Optional: $role, $readOnly, $granted --}}
@php
    $editing  = isset($role);
    $readOnly = $readOnly ?? false;
    $locked   = $editing && $role->is_system;   // system roles: level can't change
    $granted  = $granted ?? [];
@endphp

<form method="POST" action="{{ $editing ? route('admin.roles.update', $role) : route('admin.roles.store') }}" class="space-y-6">
    @csrf
    @error('role')
        <div role="alert" class="rounded-xl bg-red-50 p-4 text-sm text-red-800 ring-1 ring-red-200">{{ $message }}</div>
    @enderror
    @if ($editing) @method('PUT') @endif

    <x-ui.card title="Details">
        <div class="grid gap-5 sm:grid-cols-2">
            @if ($editing)
                <div>
                    <p class="mb-1 text-sm font-medium text-slate-700">Name</p>
                    <code class="block rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700 ring-1 ring-inset ring-slate-200">{{ $role->name }}</code>
                    <p class="mt-1 text-xs text-slate-500">The name can't be changed.</p>
                </div>
            @else
                <x-ui.input name="name" label="Name (slug)" placeholder="e.g. team-lead" required
                            hint="Lowercase letters, numbers and hyphens. Can't be changed later." />
            @endif

            <x-ui.input name="label" label="Label" :value="$role->label ?? null" :disabled="$readOnly" required />

            <div class="sm:col-span-2">
                <x-ui.textarea name="description" label="Description" :value="$role->description ?? null" :disabled="$readOnly" />
            </div>

            <div>
                @if ($locked || $readOnly)
                    <x-ui.select name="level" label="Level" disabled hint="{{ $locked ? 'System role levels are fixed.' : '' }}">
                        <option>{{ $role->level }}</option>
                    </x-ui.select>
                    @unless ($readOnly)<input type="hidden" name="level" value="{{ $role->level }}">@endunless
                @else
                    <x-ui.select name="level" label="Level" placeholder="Choose a level" required
                                 hint="Users with this role sit below anyone at a higher level. You can only pick levels below your own.">
                        @foreach ($levels as $level)
                            <option value="{{ $level }}" @selected((string) old('level', $role->level ?? '') === (string) $level)>{{ $level }}</option>
                        @endforeach
                    </x-ui.select>
                @endif
            </div>
        </div>
    </x-ui.card>

    <x-ui.card title="Permissions">
        @error('permissions')<p class="mb-3 rounded-lg bg-red-50 p-3 text-sm text-red-700" role="alert">{{ $message }}</p>@enderror
        <x-permission-matrix :granted="old('permissions', $granted)" :readonly="$readOnly" />
    </x-ui.card>

    <div class="flex justify-end gap-2">
        <x-ui.button variant="secondary" :href="route('admin.roles.index')">{{ $readOnly ? 'Back' : 'Cancel' }}</x-ui.button>
        @unless ($readOnly)
            <x-ui.button type="submit">{{ $editing ? 'Save changes' : 'Create role' }}</x-ui.button>
        @endunless
    </div>
</form>