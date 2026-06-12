@php $categoryId = $categoryId ?? null; @endphp

<form
    x-data="{
        categoryId: {{ $categoryId ? (int) $categoryId : 'null' }},
        isEdit: {{ $categoryId ? 'true' : 'false' }},
        loading: {{ $categoryId ? 'true' : 'false' }},
        saving: false,
        errors: {},
        form: { name: '', description: '' },

        async init() {
            if (this.categoryId) await this.loadData();
        },
        async loadData() {
            this.loading = true;
            try {
                const res = await window.api.get('/api/categories/' + this.categoryId);
                const d = res.data;
                this.form = { name: d.name, description: d.description ?? '' };
            } catch (e) {
                this.$store.toasts.error('Gagal memuat data: ' + e.message);
            } finally { this.loading = false; }
        },
        async submit() {
            this.saving = true; this.errors = {};
            const payload = { name: this.form.name, description: this.form.description };
            try {
                const res = this.categoryId
                    ? await window.api.put('/api/categories/' + this.categoryId, payload)
                    : await window.api.post('/api/categories', payload);
                window.flash.set(res.message || 'Kategori disimpan', 'success');
                window.location.href = '{{ route('categories.index') }}';
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
        <x-ui.card title="Data Kategori">
            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Nama Kategori <span class="text-danger-500">*</span></label>
                    <input type="text" x-model="form.name" placeholder="cth. Perangkat Jaringan"
                        class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" :class="err('name') && 'border-danger-400'" />
                    <p x-show="err('name')" x-text="err('name')" class="mt-1 text-xs text-danger-600"></p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Deskripsi</label>
                    <textarea x-model="form.description" rows="3" placeholder="Deskripsi kategori (opsional)"
                        class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" :class="err('description') && 'border-danger-400'"></textarea>
                    <p x-show="err('description')" x-text="err('description')" class="mt-1 text-xs text-danger-600"></p>
                </div>
            </div>
        </x-ui.card>

        <div class="flex items-center justify-end gap-2">
            <x-ui.button variant="outline" :href="route('categories.index')">Batal</x-ui.button>
            <x-ui.button type="submit" icon="check" ::disabled="saving || !form.name">
                <span x-text="saving ? 'Menyimpan...' : 'Simpan Kategori'"></span>
            </x-ui.button>
        </div>
    </div>
</form>
