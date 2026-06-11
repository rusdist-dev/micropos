@php $supplierId = $supplierId ?? null; @endphp

<form
    x-data="{
        supplierId: {{ $supplierId ? (int) $supplierId : 'null' }},
        loading: {{ $supplierId ? 'true' : 'false' }},
        saving: false,
        errors: {},
        form: { name: '', phone: '', address: '', is_active: true },

        async init() { if (this.supplierId) await this.loadData(); },
        async loadData() {
            this.loading = true;
            try {
                const res = await window.api.get('/api/suppliers/' + this.supplierId);
                const d = res.data;
                this.form = { name: d.name, phone: d.phone ?? '', address: d.address ?? '', is_active: d.is_active };
            } catch (e) { this.$store.toasts.error('Gagal memuat data: ' + e.message); } finally { this.loading = false; }
        },
        async submit() {
            this.saving = true; this.errors = {};
            try {
                const res = this.supplierId
                    ? await window.api.put('/api/suppliers/' + this.supplierId, this.form)
                    : await window.api.post('/api/suppliers', this.form);
                window.flash.set(res.message || 'Pemasok disimpan', 'success');
                window.location.href = '{{ route('suppliers.index') }}';
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
        <x-ui.card title="Data Pemasok">
            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Nama Pemasok <span class="text-danger-500">*</span></label>
                    <input type="text" x-model="form.name" placeholder="cth. PT Distributor Jaya"
                        class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" :class="err('name') && 'border-danger-400'" />
                    <p x-show="err('name')" x-text="err('name')" class="mt-1 text-xs text-danger-600"></p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Telepon</label>
                    <input type="text" x-model="form.phone" placeholder="cth. 021-5550123"
                        class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Alamat</label>
                    <textarea rows="3" x-model="form.address" placeholder="Alamat lengkap (opsional)"
                        class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card title="Status">
            <label class="flex items-center gap-3">
                <input type="checkbox" x-model="form.is_active" class="rounded text-primary-600 focus:ring-primary-500" />
                <span class="text-sm text-gray-700">Pemasok aktif</span>
            </label>
        </x-ui.card>

        <div class="flex items-center justify-end gap-2">
            <x-ui.button variant="outline" :href="route('suppliers.index')">Batal</x-ui.button>
            <x-ui.button type="submit" icon="check" ::disabled="saving || !form.name">
                <span x-text="saving ? 'Menyimpan...' : 'Simpan Pemasok'"></span>
            </x-ui.button>
        </div>
    </div>
</form>
