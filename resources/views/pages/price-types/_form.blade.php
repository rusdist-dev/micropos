@php $priceTypeId = $priceTypeId ?? null; @endphp

<form
    x-data="{
        priceTypeId: {{ $priceTypeId ? (int) $priceTypeId : 'null' }},
        isEdit: {{ $priceTypeId ? 'true' : 'false' }},
        loading: {{ $priceTypeId ? 'true' : 'false' }},
        saving: false,
        errors: {},
        form: { name: '', code: '', sort_order: 0, is_active: true },

        async init() {
            if (this.priceTypeId) await this.loadData();
        },
        async loadData() {
            this.loading = true;
            try {
                const res = await window.api.get('/api/price-types/' + this.priceTypeId);
                const d = res.data;
                this.form = { name: d.name, code: d.code, sort_order: d.sort_order, is_active: d.is_active };
            } catch (e) {
                this.$store.toasts.error('Gagal memuat data: ' + e.message);
            } finally { this.loading = false; }
        },
        syncCode() {
            if (this.isEdit) return;
            this.form.code = (this.form.name || '').toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
        },
        async submit() {
            this.saving = true; this.errors = {};
            const payload = { name: this.form.name, sort_order: this.form.sort_order || 0, is_active: this.form.is_active };
            if (! this.isEdit) payload.code = this.form.code;
            try {
                const res = this.priceTypeId
                    ? await window.api.put('/api/price-types/' + this.priceTypeId, payload)
                    : await window.api.post('/api/price-types', payload);
                window.flash.set(res.message || 'Tipe harga disimpan', 'success');
                window.location.href = '{{ route('price-types.index') }}';
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
        <x-ui.card title="Data Tipe Harga">
            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Nama Tipe Harga <span class="text-danger-500">*</span></label>
                    <input type="text" x-model="form.name" @input="syncCode()" placeholder="cth. Reseller"
                        class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" :class="err('name') && 'border-danger-400'" />
                    <p x-show="err('name')" x-text="err('name')" class="mt-1 text-xs text-danger-600"></p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Kode <span class="text-danger-500">*</span></label>
                    <input type="text" x-model="form.code" :readonly="isEdit" placeholder="cth. reseller"
                        class="block w-full rounded-lg border-gray-300 font-mono text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 read-only:bg-gray-50 read-only:text-gray-500" :class="err('code') && 'border-danger-400'" />
                    <p class="mt-1 text-xs text-gray-400" x-text="isEdit ? 'Kode tidak dapat diubah.' : 'Dibuat otomatis dari nama.'"></p>
                    <p x-show="err('code')" x-text="err('code')" class="mt-1 text-xs text-danger-600"></p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Urutan Tampil</label>
                    <input type="number" min="0" x-model.number="form.sort_order"
                        class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                </div>
            </div>
        </x-ui.card>

        <x-ui.card title="Status">
            <label class="flex items-center gap-3">
                <input type="checkbox" x-model="form.is_active" class="rounded text-primary-600 focus:ring-primary-500" />
                <span class="text-sm text-gray-700">Aktif (bisa dipilih di kasir)</span>
            </label>
        </x-ui.card>

        <div class="flex items-center justify-end gap-2">
            <x-ui.button variant="outline" :href="route('price-types.index')">Batal</x-ui.button>
            <x-ui.button type="submit" icon="check" ::disabled="saving || !form.name || !form.code">
                <span x-text="saving ? 'Menyimpan...' : 'Simpan Tipe Harga'"></span>
            </x-ui.button>
        </div>
    </div>
</form>
