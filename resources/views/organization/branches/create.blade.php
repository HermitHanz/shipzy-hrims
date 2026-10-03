<x-layouts.app title="New branch">
    <x-slot:header>
        <x-ui.page-header title="New branch" subtitle="Employees are assigned to a branch when they are created." />
    </x-slot:header>
    <div class="max-w-3xl">
        @include('organization.branches._form')
    </div>
</x-layouts.app>
