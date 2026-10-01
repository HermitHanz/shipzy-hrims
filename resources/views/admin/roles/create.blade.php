<x-layouts.app title="New role">
    <x-slot:header>
        <x-ui.page-header title="New role" subtitle="Pick a level and the permissions this role grants." />
    </x-slot:header>
    <div class="max-w-5xl">
        @include('admin.roles._form', ['levels' => $levels])
    </div>
</x-layouts.app>