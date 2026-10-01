<x-layouts.app title="Edit employee">
    <x-slot:header><x-ui.page-header title="Edit employee" :subtitle="$employee->employee_number" /></x-slot:header>
    @include('employees._form', compact('employee', 'branches', 'departments', 'managers', 'types'))
</x-layouts.app>