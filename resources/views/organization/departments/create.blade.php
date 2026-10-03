<x-layouts.app title="New department">
    <x-slot:header>
        <x-ui.page-header title="New department" subtitle="Departments can sit inside a parent department." />
    </x-slot:header>
    <div class="max-w-3xl">
        @include('organization.departments._form', ['parents' => $parents, 'heads' => $heads])
    </div>
</x-layouts.app>
