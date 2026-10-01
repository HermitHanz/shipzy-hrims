<x-layouts.focused title="Welcome">
    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight">Welcome, {{ $employee->first_name }}!</h1>
        <p class="mt-1 text-sm text-slate-500">Confirm a few details to get started. You can't use the app until this is done.</p>
    </div>

    <form method="POST" action="{{ route('onboarding.update') }}" class="space-y-6">
        @csrf @method('PUT')

        <x-ui.card title="Your details">
            @include('employees.partials.personal-fields', ['detail' => $employee->personalDetail, 'phone' => $employee->phone, 'required' => true])
        </x-ui.card>

        <x-ui.card title="Data privacy notice">
            @php $notice = \App\Support\Settings\Settings::value('onboarding.privacy_notice'); @endphp
            <div class="max-h-48 overflow-y-auto rounded-lg bg-slate-50 p-4 text-sm text-slate-600 ring-1 ring-inset ring-slate-200" tabindex="0" aria-label="Data privacy notice">
                @if (filled($notice))
                    {!! nl2br(e($notice)) !!}
                @else
                    <p>The data privacy notice hasn't been published yet. Please contact HR before continuing.</p>
                @endif
            </div>
            <label class="mt-4 flex items-start gap-3 text-sm text-slate-700">
                <input type="checkbox" name="accept_privacy" value="1" required class="mt-0.5 size-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500" @if ($errors->has('accept_privacy')) aria-invalid="true" aria-describedby="accept_privacy-error" @endif>
                <span>I have read and understood the data privacy notice.</span>
            </label>
            @error('accept_privacy')<p id="accept_privacy-error" class="mt-2 text-xs text-red-600" role="alert">{{ $message }}</p>@enderror
        </x-ui.card>

        <p class="text-sm text-slate-500">You can add your government IDs, emergency contact and bank details later from <strong>My Profile</strong>.</p>

        <div class="flex justify-end"><x-ui.button type="submit">Save and continue</x-ui.button></div>
    </form>
</x-layouts.focused>