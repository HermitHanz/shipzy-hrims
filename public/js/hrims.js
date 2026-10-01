document.addEventListener('alpine:init', () => {
    Alpine.data('permissionSection', (opts = {}) => ({
        open: opts.open ?? true,
        checked: 0,
        total: 0,
        init() { this.$nextTick(() => this.refresh()); },
        enabled(scope) {
            return [...(scope || this.$refs.body).querySelectorAll('input[data-perm]:not(:disabled)')];
        },
        state(boxes) {
            const on = boxes.filter((b) => b.checked).length;
            return { all: boxes.length > 0 && on === boxes.length, some: on > 0 && on < boxes.length, none: boxes.length === 0 };
        },
        refresh() {
            const all = [...this.$refs.body.querySelectorAll('input[data-perm]')];
            this.total = all.length;
            this.checked = all.filter((b) => b.checked).length;

            this.$refs.body.querySelectorAll('[data-row]').forEach((row) => {
                const toggle = row.querySelector('input[data-row-toggle]');
                if (!toggle) return;
                const s = this.state(this.enabled(row));
                toggle.disabled = s.none;
                toggle.checked = s.all;
                toggle.indeterminate = s.some;
            });

            const mt = this.$refs.moduleToggle;
            if (mt) {
                const s = this.state(this.enabled());
                mt.disabled = s.none;
                mt.checked = s.all;
                mt.indeterminate = s.some;
            }
        },
        toggleAll(e) { this.enabled().forEach((b) => (b.checked = e.target.checked)); this.refresh(); },
        toggleRow(e) { this.enabled(e.target.closest('[data-row]')).forEach((b) => (b.checked = e.target.checked)); this.refresh(); },
    }));

    Alpine.data('modal', () => ({
        open: false,
        opener: null,
        focusable() {
            return [...this.$refs.panel.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled]):not([type=hidden]), select:not([disabled]), textarea:not([disabled])'
            )];
        },
        show() {
            this.opener = document.activeElement;
            this.open = true;
            this.$nextTick(() => (this.$refs.panel.querySelector('[data-autofocus]') || this.focusable()[0])?.focus());
        },
        hide() {
            this.open = false;
            this.$nextTick(() => this.opener?.focus());
        },
        trap(e) {
            const f = this.focusable();
            if (!f.length) return;
            const first = f[0], last = f[f.length - 1];
            if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
            else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
        },
    }));

    // Masked value + transient reveal (auto re-masks after 30s, value lives only in memory, never logged)
    Alpine.data('maskedField', (cfg) => ({
        url: cfg.url, field: cfg.field, accountId: cfg.accountId ?? null,
        value: null, busy: false, error: '', left: 0, timer: null,
        get revealed() { return this.value !== null; },
        async reveal() {
            if (this.busy) return;
            this.busy = true; this.error = '';
            try {
                const res = await fetch(this.url, {
                    method: 'POST', credentials: 'same-origin', cache: 'no-store',
                    headers: {
                        'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ field: this.field, bank_account_id: this.accountId }),
                });
                if (!res.ok) {
                    this.error = ({
                        401: 'Your session has expired. Please sign in again.',
                        403: "You don't have permission to reveal this.",
                        419: 'Your session has expired. Reload the page and try again.',
                        422: 'This value could not be revealed.',
                        429: 'Too many reveals. Wait a minute and try again.',
                    })[res.status] || 'Something went wrong. Try again.';
                    return;
                }
                const data = await res.json();
                this.value = String(data.value ?? '');
                this.start();
            } catch (e) {
                this.error = 'Network error. Try again.';
            } finally {
                this.busy = false;
            }
        },
        start() { this.stop(); this.left = 30; this.timer = setInterval(() => { if (--this.left <= 0) this.hide(); }, 1000); },
        stop() { if (this.timer) { clearInterval(this.timer); this.timer = null; } },
        hide() { this.stop(); this.value = null; this.left = 0; },
        destroy() { this.hide(); },
    }));

    // Government ID form: only "changed" or "removed" IDs are ever submitted
    Alpine.data('governmentIds', (types, errored = []) => ({
        mode: Object.fromEntries(types.map((t) => [t, errored.includes(t) ? 'change' : 'idle'])),
        set(t, m) { this.mode[t] = m; if (m === 'change') this.$nextTick(() => this.$refs[t]?.focus()); },
        get dirty() { return Object.values(this.mode).some((m) => m !== 'idle'); },
    }));

    // Emergency contacts repeater (max 3, one primary)
    Alpine.data('emergencyContacts', (initial) => ({
        contacts: initial,
        primary: Math.max(0, initial.findIndex((c) => c.is_primary)),
        blank() { return { key: Math.random().toString(36).slice(2), name: '', relationship: '', phone: '', alt_phone: '', address: '', is_primary: false }; },
        add() { if (this.contacts.length < 3) this.contacts.push(this.blank()); },
        remove(i) {
            this.contacts.splice(i, 1);
            if (this.primary === i) this.primary = 0; else if (this.primary > i) this.primary--;
        },
    }));
});