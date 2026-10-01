@props(['title', 'description' => null, 'icon' => 'users'])
<div class="px-6 py-12 text-center">
    <span class="mx-auto grid size-12 place-items-center rounded-full bg-slate-100 text-slate-400"><x-ui.icon :name="$icon" class="size-6" /></span>
    <h3 class="mt-4 text-sm font-semibold">{{ $title }}</h3>
    @if ($description)<p class="mt-1 text-sm text-slate-500">{{ $description }}</p>@endif
    @if (! $slot->isEmpty())<div class="mt-4">{{ $slot }}</div>@endif
</div>