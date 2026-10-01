@props(['title' => null])
<x-layouts.base :title="$title">
    <div class="flex min-h-full flex-col justify-center px-4 py-12 sm:px-6">
        <div class="mx-auto w-full max-w-md">
            <div class="mb-8 text-center">
                <span class="mx-auto grid size-12 place-items-center rounded-xl bg-brand-600 text-lg font-bold text-white">S</span>
                <h1 class="mt-4 text-2xl font-semibold">{{ config('app.name') }}</h1>
            </div>
            <div class="rounded-2xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
                {{ $slot }}
            </div>
            @php
                $companyName = \App\Support\Settings\Settings::value('company.name') ?: config('app.name');
                $hrEmail     = \App\Support\Settings\Settings::value('company.hr_contact_email');
            @endphp
            <footer class="mt-8 text-center text-xs text-slate-500">
                <p>&copy; {{ now()->year }} {{ $companyName }}</p>
                @if ($hrEmail)
                    <p class="mt-1">Need help? Contact HR at
                        <a href="mailto:{{ $hrEmail }}" class="font-medium text-brand-600 hover:text-brand-700">{{ $hrEmail }}</a></p>
                @endif
            </footer>
        </div>
    </div>
</x-layouts.base>