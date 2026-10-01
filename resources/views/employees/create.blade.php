<x-layouts.app title="New employee">
    <x-slot:header><x-ui.page-header title="New employee" subtitle="You can create their login right after saving." /></x-slot:header>
    @include('employees._form', compact('branches', 'departments', 'managers', 'types'))
</x-layouts.app>