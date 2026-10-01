<x-ui.page-header title="Layout test" subtitle="If you can see this, the shell is working.">
    <x-ui.button variant="secondary">Secondary</x-ui.button>
    <x-ui.button>Primary</x-ui.button>
</x-ui.page-header>

<div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
    @foreach (['Headcount' => '128', 'Present today' => '112', 'On leave' => '9', 'Pending requests' => '7'] as $label => $value)
        <x-ui.card>
            <p class="text-sm text-slate-500">{{ $label }}</p>
            <p class="mt-1 text-3xl font-semibold">{{ $value }}</p>
        </x-ui.card>
    @endforeach
</div>

<x-ui.card title="Sample table" class="mt-6">
    <x-slot:actions><x-ui.button variant="danger">Danger</x-ui.button></x-slot:actions>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-slate-200 text-slate-500">
                <tr><th class="py-2 pr-4">Name</th><th class="py-2 pr-4">Department</th><th class="py-2">Status</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <tr><td class="py-3 pr-4">Juan Dela Cruz</td><td class="py-3 pr-4">Engineering</td><td class="py-3">Active</td></tr>
                <tr><td class="py-3 pr-4">Maria Santos</td><td class="py-3 pr-4">HR</td><td class="py-3">Active</td></tr>
            </tbody>
        </table>
    </div>
</x-ui.card>