@php
    $user = auth()->user();
    $name = $user->name ?? 'Guest User';
@endphp
<header class="sticky top-0 z-20 flex h-16 items-center gap-4 border-b border-slate-200 bg-white/80 px-4 backdrop-blur sm:px-6 lg:px-8">
    <button type="button" @click="sidebarOpen = true" class="-ml-1 rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden" aria-label="Open sidebar">
        <x-ui.icon name="bars-3" class="size-6" />
    </button>

    <div class="flex-1"></div>

    {{-- User menu --}}
    <div x-data="{ open: false }" class="relative" x-on:click.outside="open = false">

        {{-- User menu button --}}
        <button
            type="button"
            x-on:click="open = !open"
            class="flex items-center gap-2 rounded-lg p-1.5 hover:bg-slate-100"
        >
            <span class="grid size-8 place-items-center rounded-full bg-brand-100 text-sm font-semibold text-brand-700">
                {{ strtoupper(substr($name, 0, 1)) }}
            </span>

            <span class="hidden text-sm font-medium sm:block">
                {{ $name }}
            </span>

            <x-ui.icon name="chevron-down" class="size-4 text-slate-400" />
        </button>

        {{-- Dropdown --}}
        <div
            x-show="open"
            x-cloak
            x-transition
            class="absolute right-0 mt-2 w-48 rounded-xl bg-white py-1 shadow-lg ring-1 ring-slate-200"
        >
            <a href="{{ Route::has('profile.show') ? route('profile.show') : '#' }}" class="block px-4 py-2 text-sm text-slate-700 hover:bg-slate-50">My profile</a>

            <form method="POST" action="{{ Route::has('logout') ? route('logout') : '#' }}">
                @csrf

                <button
                    type="submit"
                    class="flex w-full items-center gap-2 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50"
                >
                    <x-ui.icon name="logout" class="size-4" />
                    Sign out
                </button>
            </form>
        </div>
    </div>
</header>