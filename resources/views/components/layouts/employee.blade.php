@props(['title' => null])
<x-layouts.shell 
    :title="$title" 
    portal="Employee Portal" 
    :nav="config('navigation.employee')">

    @isset($header)
        <x-slot:header>
            {{ $header }}
            </x-slot:header>
    @endisset
    {{ $slot }}
</x-layouts.shell>