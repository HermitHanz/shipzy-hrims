@props(['title' => null])
<x-layouts.base :title="$title">
    <div class="min-h-full">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex h-16 max-w-3xl items-center justify-between px-4 sm:px-6">
                <div class="flex items-center gap-3">
                    <span class="grid size-8 place-items-center rounded-lg bg-brand-600 text-sm font-bold text-white">S</span>
                    <span class="text-sm font-semibold">{{ config('app.name') }}</span>
                </div>
                <form method="POST" action="{{ Route::has('logout') ? route('logout') : '#' }}">
                    @csrf
                    <button type="submit" class="text-sm font-medium text-slate-600 hover:text-slate-900">Sign out</button>
                </form>
            </div>
        </header>
        <main class="mx-auto max-w-3xl p-4 sm:p-6">
            @if ($errors->any())
                <div role="alert" class="mb-6 rounded-xl bg-red-50 p-4 text-sm text-red-800 ring-1 ring-red-200">Please fix the highlighted fields below.</div>
            @endif
            {{ $slot }}
        </main>
    </div>
</x-layouts.base>