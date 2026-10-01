{{-- $groupKey, $name, $field, $values, $canEdit, $isSubmitted --}}
@php
    $id       = "setting-{$groupKey}-{$name}";
    $type     = $field['type'] ?? 'text';
    $label    = $field['label'] ?? \Illuminate\Support\Str::headline($name);
    $help     = $field['help'] ?? null;
    $stored   = array_key_exists($name, $values) ? $values[$name] : ($field['default'] ?? null);
    $value    = $isSubmitted ? old($name, $stored) : $stored;
    $hasError = $isSubmitted && $errors->has($name);
    $message  = $hasError ? $errors->first($name) : null;

    // Client-side hints only. The server enforces the real rules.
    $rules = $field['rules'] ?? [];
    $rules = collect(is_array($rules) ? $rules : explode('|', (string) $rules))->filter(fn ($r) => is_string($r));
    $required = $rules->contains('required');
    $min  = optional($rules->first(fn ($r) => str_starts_with($r, 'min:')), fn ($r) => substr($r, 4));
    $max  = optional($rules->first(fn ($r) => str_starts_with($r, 'max:')), fn ($r) => substr($r, 4));
    $step = $rules->contains('integer') ? '1' : 'any';

    $describedBy = trim(($help ? "{$id}-help " : '') . ($hasError ? "{$id}-error" : ''));
    $control = 'block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset focus:ring-2 focus:ring-brand-500 disabled:bg-slate-50 disabled:text-slate-500 '
             . ($hasError ? 'ring-red-500' : 'ring-slate-300');
@endphp

<div>
    @if ($type === 'boolean')
        @if ($canEdit)<input type="hidden" name="{{ $name }}" value="0">@endif
        <label for="{{ $id }}" class="flex items-start gap-3">
            <input id="{{ $id }}" type="checkbox" name="{{ $name }}" value="1" @checked((bool) $value) @disabled(! $canEdit)
                   @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                   @if ($hasError) aria-invalid="true" @endif
                   class="mt-0.5 size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500 disabled:opacity-60">
            <span>
                <span class="text-sm font-medium text-slate-700">{{ $label }}</span>
                @if ($help)<span id="{{ $id }}-help" class="mt-0.5 block text-xs text-slate-500">{{ $help }}</span>@endif
            </span>
        </label>
    @else
        <label for="{{ $id }}" class="mb-1 block text-sm font-medium text-slate-700">{{ $label }}</label>

        @if ($type === 'textarea')
            <textarea id="{{ $id }}" name="{{ $name }}" rows="5" @disabled(! $canEdit) @required($required)
                      @if ($max) maxlength="{{ $max }}" @endif
                      @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                      @if ($hasError) aria-invalid="true" @endif
                      class="{{ $control }}">{{ $value }}</textarea>

        @elseif ($type === 'select')
            <select id="{{ $id }}" name="{{ $name }}" @disabled(! $canEdit) @required($required)
                    @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                    @if ($hasError) aria-invalid="true" @endif
                    class="{{ $control }}">
                @foreach (($field['options'] ?? []) as $optValue => $optLabel)
                    <option value="{{ $optValue }}" @selected((string) $value === (string) $optValue)>{{ $optLabel }}</option>
                @endforeach
            </select>

        @else
            <input id="{{ $id }}" name="{{ $name }}" type="{{ in_array($type, ['email', 'number']) ? $type : 'text' }}"
                   value="{{ $value }}" @disabled(! $canEdit) @required($required)
                   @if ($type === 'number')
                       step="{{ $step }}" @if ($min !== null) min="{{ $min }}" @endif @if ($max !== null) max="{{ $max }}" @endif
                   @elseif ($max) maxlength="{{ $max }}" @endif
                   @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
                   @if ($hasError) aria-invalid="true" @endif
                   class="{{ $control }}">
        @endif

        @if ($help)<p id="{{ $id }}-help" class="mt-1 text-xs text-slate-500">{{ $help }}</p>@endif
    @endif

    @if ($hasError)<p id="{{ $id }}-error" class="mt-1 text-xs text-red-600" role="alert">{{ $message }}</p>@endif
</div>