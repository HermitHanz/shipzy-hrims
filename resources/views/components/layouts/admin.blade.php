@props(['title' => null])

<x-layouts.shell
    :title="$title"
    portal="HR Admin"
    :nav="config('navigation.admin')"
>
    @isset($header)
        <x-slot:header>
            {{ $header }}
        </x-slot:header>
    @endisset

    {{ $slot }}
</x-layouts.shell>