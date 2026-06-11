@php $roleId = $roleId ?? null; @endphp

<form
    x-data="{
        roleId: {{ $roleId ? (int) $roleId : 'null' }},
        isEdit: {{ $roleId ? 'true' : 'false' }},
        isCore: false,
        loading: true, saving: false, errors: {},
        name: '',
        permissions: [],   // semua nama permission
        selected: [],      // permission terpilih

        labels: {
            dashboard: 'Dashboard', products: 'Produk', 'price-types': 'Tipe Harga',
            suppliers: 'Pemasok', 'stock-opnames': 'Stok Opname', supplies: 'Supply Barang',
            returns: 'Retur', customers: 'Pelanggan', services: 'Jasa',
            transactions: 'Transaksi', users: 'Pengguna', roles: 'Role & Akses',
        },
        actionLabels: { view: 'Lihat', create: 'Tambah', edit: 'Ubah', delete: 'Hapus', 'view-all': 'Lihat Semua', finalize: 'Finalisasi' },

        async init() {
            await this.loadPermissions();
            if (this.roleId) await this.loadRole();
            else this.loading = false;
        },
        async loadPermissions() {
            try {
                const res = await window.api.get('/api/permissions');
                this.permissions = res.data;
            } catch (e) { this.$store.toasts.error('Gagal memuat permission: ' + e.message); }
        },
        async loadRole() {
            try {
                const res = await window.api.get('/api/roles/' + this.roleId);
                this.name = res.data.name;
                this.selected = res.data.permissions ?? [];
                this.isCore = res.data.is_core;
            } catch (e) { this.$store.toasts.error('Gagal memuat role: ' + e.message); }
            finally { this.loading = false; }
        },
        get groups() {
            const map = {};
            for (const p of this.permissions) {
                const key = p.split('.')[0];
                (map[key] = map[key] || []).push(p);
            }
            return Object.keys(map).map((key) => ({ key, label: this.labels[key] ?? key, perms: map[key] }));
        },
        actionLabel(p) {
            const a = p.split('.').slice(1).join('.');
            return this.actionLabels[a] ?? a;
        },
        groupAllChecked(perms) { return perms.every((p) => this.selected.includes(p)); },
        toggleGroup(perms, checked) {
            if (checked) {
                perms.forEach((p) => { if (! this.selected.includes(p)) this.selected.push(p); });
            } else {
                this.selected = this.selected.filter((p) => ! perms.includes(p));
            }
        },
        async submit() {
            this.saving = true; this.errors = {};
            const payload = { name: this.name, permissions: this.selected };
            try {
                const res = this.roleId
                    ? await window.api.put('/api/roles/' + this.roleId, payload)
                    : await window.api.post('/api/roles', payload);
                window.flash.set(res.message || 'Role disimpan', 'success');
                window.location.href = '{{ route('roles.index') }}';
            } catch (e) {
                this.saving = false;
                if (e.status === 422) { this.errors = e.errors; this.$store.toasts.error('Periksa kembali isian form.'); }
                else this.$store.toasts.error(e.message);
            }
        },
        err(f) { return this.errors[f]?.[0] ?? null; },
    }"
    @submit.prevent="submit()"
    class="space-y-6"
>
    <template x-if="loading"><div class="py-10"><x-ui.loading-spinner size="lg" /></div></template>

    <div x-show="!loading" class="space-y-6">
        <x-ui.card title="Data Role">
            <div class="max-w-md">
                <label class="mb-1 block text-sm font-medium text-gray-700">Nama Role <span class="text-danger-500">*</span></label>
                <input type="text" x-model="name" :readonly="isCore" placeholder="cth. supervisor"
                    class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 read-only:bg-gray-50 read-only:text-gray-500"
                    :class="err('name') && 'border-danger-400'" />
                <p x-show="isCore" class="mt-1 text-xs text-gray-400">Nama role inti tidak dapat diubah.</p>
                <p x-show="err('name')" x-text="err('name')" class="mt-1 text-xs text-danger-600"></p>
            </div>
        </x-ui.card>

        <x-ui.card title="Hak Akses (Permission)">
            <x-slot:actions>
                <span class="text-xs text-gray-400"><span x-text="selected.length"></span> dipilih</span>
            </x-slot:actions>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <template x-for="g in groups" :key="g.key">
                    <div class="rounded-lg border border-gray-200 p-3">
                        <label class="mb-2 flex items-center gap-2 border-b border-gray-100 pb-2">
                            <input type="checkbox" :checked="groupAllChecked(g.perms)" @change="toggleGroup(g.perms, $event.target.checked)"
                                class="rounded text-primary-600 focus:ring-primary-500" />
                            <span class="text-sm font-semibold text-gray-800" x-text="g.label"></span>
                        </label>
                        <div class="space-y-1.5">
                            <template x-for="p in g.perms" :key="p">
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" :value="p" x-model="selected" class="rounded text-primary-600 focus:ring-primary-500" />
                                    <span class="text-sm text-gray-600" x-text="actionLabel(p)"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </x-ui.card>

        <div class="flex items-center justify-end gap-2">
            <x-ui.button variant="outline" :href="route('roles.index')">Batal</x-ui.button>
            <x-ui.button type="submit" icon="check" ::disabled="saving || !name">
                <span x-text="saving ? 'Menyimpan...' : 'Simpan Role'"></span>
            </x-ui.button>
        </div>
    </div>
</form>
