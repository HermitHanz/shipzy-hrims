@props(['missing' => [], 'link' => true])
@if (! empty($missing))
    <div role="status" class="mb-6 rounded-xl bg-brand-50 p-4 ring-1 ring-brand-100">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-brand-700">Complete your profile</p>
                <p class="mt-1 text-sm text-slate-600">Still missing: {{ implode(', ', $missing) }}.</p>
            </div>
            @if ($link && Route::has('profile.show'))
                <x-ui.button :href="route('profile.show')">Go to My Profile</x-ui.button>
            @endif
        </div>
    </div>
@endif