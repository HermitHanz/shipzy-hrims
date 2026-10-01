@props(['name', 'label' => null, 'placeholder' => null, 'hint' => null])
@php $hasError = $errors->has($name); @endphp
<div>
    @if ($label)
        <label for="{{ $name }}" class="mb-1 block text-sm font-medium text-slate-700">{{ $label }}</label>
    @endif
    <select id="{{ $name }}" name="{{ $name }}"
            @if ($hasError) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif
            {{ $attributes->class([
                'block w-full rounded-lg border-0 px-3 py-2 text-sm ring-1 ring-inset focus:ring-2 focus:ring-brand-500 disabled:bg-slate-50 disabled:text-slate-500',
                'ring-slate-300' => ! $hasError,
                'ring-red-500'   => $hasError,
            ]) }}>
        @if ($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
        {{ $slot }}
    </select>
    @if ($hint && ! $hasError)<p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>@endif
    @error($name)<p id="{{ $name }}-error" class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>