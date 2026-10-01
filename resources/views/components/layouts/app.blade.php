@props(['title' => null])

<x-layouts.base :title="$title">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-2 focus:shadow">Skip to content</a>

    <div x-data="{ sidebarOpen: false }" x-on:keydown.escape.window="sidebarOpen = false">
        {{-- Mobile drawer --}}
        <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-40 lg:hidden" role="dialog" aria-modal="true" aria-label="Navigation">
            <div x-show="sidebarOpen" x-transition.opacity x-on:click="sidebarOpen = false" class="fixed inset-0 bg-slate-900/50"></div>
            <div x-show="sidebarOpen"
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
                 class="relative h-full w-64 bg-white shadow-xl">
                <x-sidebar />
            </div>
        </div>

        {{-- Desktop sidebar --}}
        <aside class="fixed inset-y-0 z-30 hidden w-64 border-r border-slate-200 bg-white lg:block">
            <x-sidebar />
        </aside>

        <div class="lg:pl-64">
            <x-topbar />
            <main id="main" class="p-4 sm:p-6 lg:p-8">
                @if (session('status'))
                    <div x-data="{ show: true }" x-show="show" role="status"
                         class="mb-6 flex items-start justify-between gap-3 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800 ring-1 ring-emerald-200">
                        <span>{{ session('status') }}</span>
                        <button type="button" x-on:click="show = false" class="text-emerald-700 hover:text-emerald-900" aria-label="Dismiss">
                            <x-ui.icon name="x-mark" class="size-4" />
                        </button>
                    </div>
                @endif

                {{ $header ?? '' }}
                {{ $slot }}
            </main>
        </div>
    </div>
</x-layouts.base>