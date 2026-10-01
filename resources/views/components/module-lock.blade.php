@props(['module'])
@php
    $svc     = app(\App\Support\Security\ModuleUnlock::class);
    $user    = auth()->user();
    $expires = $user ? $svc->expiresAt($user, $module) : null;
@endphp
@if ($expires)
    <form method="POST" action="{{ route('module.unlock.destroy', $module) }}" class="flex items-center gap-3">
        @csrf @method('DELETE')
        <span class="hidden text-xs text-slate-500 sm:inline">Unlocked · expires {{ $expires->diffForHumans() }}</span>
        <x-ui.button type="submit" variant="secondary"><x-ui.icon name="lock-closed" class="size-4" /> Lock now</x-ui.button>
    </form>
@endif