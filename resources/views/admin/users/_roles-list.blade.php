{{-- $roles (already filtered by the controller), $selected (array of names), $readonly (bool) --}}
<div class="space-y-2">
    @foreach ($roles as $role)
        @php $checked = in_array($role->name, $selected, true); @endphp
        <label class="flex items-start gap-3 rounded-lg p-3 ring-1 ring-slate-200 {{ $readonly ? 'bg-slate-50' : 'cursor-pointer hover:bg-slate-50' }}">
            <input type="checkbox"
                   @unless ($readonly) name="roles[]" value="{{ $role->name }}" @endunless
                   @checked($checked) @disabled($readonly)
                   class="mt-0.5 size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500 disabled:opacity-60">
            <span class="min-w-0">
                <span class="flex flex-wrap items-center gap-2">
                    <span class="text-sm font-medium">{{ $role->label ?: $role->name }}</span>
                    <x-ui.badge>Level {{ $role->level }}</x-ui.badge>
                </span>
                @if ($role->description)<span class="mt-0.5 block text-xs text-slate-500">{{ $role->description }}</span>@endif
            </span>
        </label>
    @endforeach
</div>