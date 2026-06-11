@php $customerId = $customerId ?? null; @endphp

<form
    x-data="{
        customerId: {{ $customerId ? (int) $customerId : 'null' }},
        loading: {{ $customerId ? 'true' : 'false' }},
        saving: false,
        errors: {},
        form: { name: '', phone: '', address: '' },

        async init() { if (this.customerId) await this.loadData(); },
        async loadData() {
            this.loading = true;
            try {
                const res = await window.api.get('/api/customers/' + this.customerId);
                const d = res.data;
                this.form = { name: d.name, phone: d.phone ?? '', address: d.address ?? '' };
            } catch (e) { this.$store.toasts.error('Gagal memuat data: ' + e.message); } finally { this.loading = false; }
        },
        async submit() {
            this.saving = true; this.errors = {};
            try {
                const res = this.customerId
                    ? await window.api.put('/api/customers/' + this.customerId, this.form)
                    : await window.api.post('/api/customers', this.form);
                window.flash.set(res.message || 'Pelanggan disimpan', 'success');
                window.location.href = '{{ route('customers.index') }}';
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
        <x-ui.card title="Data Pelanggan">
            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Nama Pelanggan <span class="text-danger-500">*</span></label>
                    <input type="text" x-model="form.name" placeholder="cth. PT Maju Jaya"
                        class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" :class="err('name') && 'border-danger-400'" />
                    <p x-show="err('name')" x-text="err('name')" class="mt-1 text-xs text-danger-600"></p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Telepon</label>
                    <input type="text" x-model="form.phone" placeholder="cth. 0812-3456-7890"
                        class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Alamat</label>
                    <textarea rows="3" x-model="form.address" placeholder="Alamat lengkap (opsional)"
                        class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
                </div>
            </div>
        </x-ui.card>

        <div class="flex items-center justify-end gap-2">
            <x-ui.button variant="outline" :href="route('customers.index')">Batal</x-ui.button>
            <x-ui.button type="submit" icon="check" ::disabled="saving || !form.name">
                <span x-text="saving ? 'Menyimpan...' : 'Simpan Pelanggan'"></span>
            </x-ui.button>
        </div>
    </div>
</form>
