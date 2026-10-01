<x-layouts.app :title="'Unlock ' . $def['label']">
    <div class="mx-auto max-w-md pt-6">
        <x-ui.card>
            <div class="text-center">
                <span class="mx-auto grid size-12 place-items-center rounded-full bg-brand-50 text-brand-600">
                    <x-ui.icon name="lock-closed" class="size-6" />
                </span>
                <h1 class="mt-4 text-lg font-semibold">{{ $def['label'] }} is protected</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $def['description'] ?? 'Confirm your password to continue.' }}</p>
            </div>

            <form method="POST" action="{{ route('module.unlock.store', $module) }}" class="mt-6 space-y-5">
                @csrf
                <x-ui.input name="password" type="password" label="Your password" autocomplete="current-password" required autofocus />

                <p class="text-xs text-slate-500">
                    @if ($def['sliding'])
                        You'll stay unlocked until you've been inactive for {{ $def['ttl'] }} {{ \Illuminate\Support\Str::plural('minute', $def['ttl']) }}.
                    @else
                        You'll stay unlocked for {{ $def['ttl'] }} {{ \Illuminate\Support\Str::plural('minute', $def['ttl']) }}.
                    @endif
                </p>

                <div class="flex justify-end gap-2">
                    <x-ui.button variant="secondary" :href="route('dashboard')">Cancel</x-ui.button>
                    <x-ui.button type="submit">Unlock</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>