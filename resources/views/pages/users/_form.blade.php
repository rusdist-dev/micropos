@php $userId = $userId ?? null; @endphp

<form
    x-data="{
        userId: {{ $userId ? (int) $userId : 'null' }},
        isEdit: {{ $userId ? 'true' : 'false' }},
        loading: {{ $userId ? 'true' : 'false' }},
        saving: false,
        errors: {},
        roles: [],
        form: { name: '', email: '', password: '', role: 'kasir', is_active: true },

        async init() {
            await this.loadRoles();
            if (this.userId) await this.loadData();
            // Sinkronkan nilai <select> ke state setelah opsi (x-for) ter-render async,
            // mencegah desync di mana select menampilkan opsi pertama tapi form.role tetap default.
            this.$nextTick(() => {
                if (this.$refs.roleSelect && this.roles.includes(this.form.role)) {
                    this.$refs.roleSelect.value = this.form.role;
                }
            });
        },
        async loadRoles() {
            try {
                const res = await window.api.get('/api/roles?all=1');
                this.roles = res.data.map((r) => r.name);
                if (! this.userId && this.roles.length && ! this.roles.includes(this.form.role)) {
                    this.form.role = this.roles[0];
                }
            } catch (e) { /* fallback ke opsi statis */ }
        },
        async loadData() {
            this.loading = true;
            try {
                const res = await window.api.get('/api/users/' + this.userId);
                const d = res.data;
                this.form = { name: d.name, email: d.email, password: '', role: d.role ?? 'kasir', is_active: d.is_active };
            } catch (e) { this.$store.toasts.error('Gagal memuat data: ' + e.message); } finally { this.loading = false; }
        },
        async submit() {
            this.saving = true; this.errors = {};
            const payload = { name: this.form.name, email: this.form.email, role: this.form.role, is_active: this.form.is_active };
            if (this.form.password) payload.password = this.form.password;
            try {
                const res = this.userId
                    ? await window.api.put('/api/users/' + this.userId, payload)
                    : await window.api.post('/api/users', { ...payload, password: this.form.password });
                window.flash.set(res.message || 'Pengguna disimpan', 'success');
                window.location.href = '{{ route('users.index') }}';
            } catch (e) {
                this.saving = false;
                if (e.status === 422) { this.errors = e.errors; this.$store.toasts.error('Periksa kembali isian form.'); }
                else this.$store.toasts.error(e.message);
            }
        },
        err(f) { return this.errors[f]?.[0] ?? null; },
    }"
    @submit.prevent="submit()"
    class="max-w-2xl space-y-6"
>
    <template x-if="loading"><div class="py-10"><x-ui.loading-spinner size="lg" /></div></template>

    <div x-show="!loading" class="space-y-6">
        <x-ui.card title="Data Pengguna">
            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Nama Lengkap <span class="text-danger-500">*</span></label>
                    <input type="text" x-model="form.name" placeholder="cth. Budi Santoso"
                        class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" :class="err('name') && 'border-danger-400'" />
                    <p x-show="err('name')" x-text="err('name')" class="mt-1 text-xs text-danger-600"></p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Email <span class="text-danger-500">*</span></label>
                    <input type="email" x-model="form.email" placeholder="cth. budi@pos.test"
                        class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" :class="err('email') && 'border-danger-400'" />
                    <p x-show="err('email')" x-text="err('email')" class="mt-1 text-xs text-danger-600"></p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">
                        <span x-text="isEdit ? 'Password Baru' : 'Password'"></span>
                        <span x-show="!isEdit" class="text-danger-500">*</span>
                    </label>
                    <input type="password" x-model="form.password" placeholder="••••••••"
                        class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" :class="err('password') && 'border-danger-400'" />
                    <p class="mt-1 text-xs text-gray-400" x-text="isEdit ? 'Kosongkan jika tidak diubah' : 'Minimal 8 karakter'"></p>
                    <p x-show="err('password')" x-text="err('password')" class="mt-1 text-xs text-danger-600"></p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Role <span class="text-danger-500">*</span></label>
                    <select x-ref="roleSelect" x-model="form.role" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                        <template x-for="r in roles" :key="r"><option :value="r" x-text="r.charAt(0).toUpperCase() + r.slice(1)"></option></template>
                    </select>
                    <p x-show="err('role')" x-text="err('role')" class="mt-1 text-xs text-danger-600"></p>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card title="Status">
            <label class="flex items-center gap-3">
                <input type="checkbox" x-model="form.is_active" class="rounded text-primary-600 focus:ring-primary-500" />
                <span class="text-sm text-gray-700">Akun aktif (bisa login)</span>
            </label>
        </x-ui.card>

        <div class="flex items-center justify-end gap-2">
            <x-ui.button variant="outline" :href="route('users.index')">Batal</x-ui.button>
            <x-ui.button type="submit" icon="check" ::disabled="saving">
                <span x-text="saving ? 'Menyimpan...' : 'Simpan Pengguna'"></span>
            </x-ui.button>
        </div>
    </div>
</form>
