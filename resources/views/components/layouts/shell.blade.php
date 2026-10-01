@props(['title' => null, 'portal' => '', 'nav' => []])

<x-layouts.base :title="$title">
    <div x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">

        {{-- Mobile drawer --}}
        <div x-show="sidebarOpen" x-cloak class="fixed inset-0 z-40 lg:hidden" role="dialog" aria-modal="true">
            <div x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false"
                 class="fixed inset-0 bg-slate-900/50"></div>
            <div x-show="sidebarOpen"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
                 class="relative h-full w-64 bg-white shadow-xl">
                <x-nav.sidebar :items="$nav" :portal="$portal" />
            </div>
        </div>

        {{-- Desktop sidebar --}}
        <aside class="fixed inset-y-0 z-30 hidden w-64 border-r border-slate-200 bg-white lg:block">
            <x-nav.sidebar :items="$nav" :portal="$portal" />
        </aside>

        {{-- Content --}}
        <div class="lg:pl-64">
            <x-nav.topbar />
            <main class="p-4 sm:p-6 lg:p-8">
                {{ $header ?? '' }}
                {{ $slot }}
            </main>
        </div>
    </div>
</x-layouts.base>