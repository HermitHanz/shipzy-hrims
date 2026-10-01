<x-layouts.app title="Temporary password">
    <x-slot:header>
        <x-ui.page-header :title="$credentials['context'] === 'reset' ? 'Password reset' : 'Account created'"
                          :subtitle="$credentials['user_name'] . ' · ' . $credentials['email']" />
    </x-slot:header>

    <x-ui.card class="max-w-2xl">
        <div role="alert" class="flex gap-3 rounded-xl bg-amber-50 p-4 text-sm text-amber-900 ring-1 ring-amber-200">
            <x-ui.icon name="warning" class="mt-0.5 size-5 shrink-0" />
            <p><strong>Copy this password now.</strong> It won't be shown again. Leaving or refreshing this page removes it.</p>
        </div>

        <div class="mt-5" x-data="{
                copied: false,
                async copy() {
                    const text = this.$refs.pw.textContent.trim();
                    try {
                        await navigator.clipboard.writeText(text);
                        this.copied = true;
                        setTimeout(() => this.copied = false, 2500);
                    } catch (e) {
                        const r = document.createRange(); r.selectNodeContents(this.$refs.pw);
                        const s = getSelection(); s.removeAllRanges(); s.addRange(r);
                    }
                }
            }">
            <p class="text-sm font-medium text-slate-700">Temporary password</p>
            <div class="mt-1 flex items-center gap-2">
                <code x-ref="pw" class="flex-1 select-all break-all rounded-lg bg-slate-900 px-4 py-3 font-mono text-base tracking-wide text-white">{{ $credentials['password'] }}</code>
                <x-ui.button variant="secondary" x-on:click="copy()">
                    <x-ui.icon name="clipboard" class="size-4" />
                    <span x-text="copied ? 'Copied!' : 'Copy'">Copy</span>
                </x-ui.button>
            </div>
            <p class="sr-only" aria-live="polite" x-text="copied ? 'Password copied to clipboard' : ''"></p>
        </div>

        <p class="mt-5 text-sm text-slate-600">
            Share it with {{ $credentials['user_name'] }} through a secure channel. They must choose a new password the first time they sign in.
        </p>

        <div class="mt-6 flex flex-wrap gap-2">
            <x-ui.button :href="route('admin.users.access', $credentials['user_id'])">Manage access</x-ui.button>
            <x-ui.button variant="secondary" :href="$credentials['back_url'] ?? route('admin.users.index')">{{ $credentials['back_label'] ?? 'Back to users' }}</x-ui.button>
        </div>
    </x-ui.card>
</x-layouts.app>