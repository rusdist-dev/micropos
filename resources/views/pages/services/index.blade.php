<x-layouts.app title="Jasa">
    <div
        x-data="{
            loading: true,
            error: null,
            items: [],
            meta: { current_page: 1, last_page: 1, total: 0 },
            search: '',
            page: 1,

            showForm: false,
            saving: false,
            editingId: null,
            errors: {},
            form: { name: '', description: '', default_price: 0, is_active: true },

            showDelete: false,
            deleting: null,

            async load() {
                this.loading = true; this.error = null;
                try {
                    const params = new URLSearchParams({ page: this.page, per_page: 10 });
                    if (this.search) params.set('search', this.search);
                    const res = await window.api.get('/api/services?' + params.toString());
                    this.items = res.data; this.meta = res.meta;
                } catch (e) { this.error = e.message; } finally { this.loading = false; }
            },
            goToPage(p) { if (p < 1 || p > this.meta.last_page) return; this.page = p; this.load(); },

            openCreate() {
                this.editingId = null; this.errors = {};
                this.form = { name: '', description: '', default_price: 0, is_active: true };
                this.showForm = true;
            },
            openEdit(s) {
                this.editingId = s.id; this.errors = {};
                this.form = { name: s.name, description: s.description ?? '', default_price: s.default_price, is_active: s.is_active };
                this.showForm = true;
            },
            async submit() {
                this.saving = true; this.errors = {};
                try {
                    const res = this.editingId
                        ? await window.api.put('/api/services/' + this.editingId, this.form)
                        : await window.api.post('/api/services', this.form);
                    this.$store.toasts.success(res.message || 'Jasa disimpan');
                    this.showForm = false; this.load();
                } catch (e) {
                    if (e.status === 422) { this.errors = e.errors; this.$store.toasts.error('Periksa isian form.'); }
                    else this.$store.toasts.error(e.message);
                } finally { this.saving = false; }
            },
            err(f) { return this.errors[f]?.[0] ?? null; },

            askDelete(s) { this.deleting = s; this.showDelete = true; },
            async doDelete() {
                try {
                    const res = await window.api.delete('/api/services/' + this.deleting.id);
                    this.$store.toasts.success(res.message || 'Jasa dihapus');
                    this.showDelete = false; this.load();
                } catch (e) { this.showDelete = false; this.$store.toasts.error(e.message); }
            },
        }"
        x-init="load(); $watch('search', () => { page = 1; load(); })"
    >
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="sm:w-80"><x-ui.search-input placeholder="Cari jasa..." model="search" /></div>
            <x-ui.button type="button" icon="plus" @click="openCreate()">Tambah Jasa</x-ui.button>
        </div>

        <div x-show="loading" class="py-10"><x-ui.loading-spinner size="lg" label="Memuat jasa..." /></div>
        <template x-if="error"><x-ui.alert variant="danger" title="Gagal memuat"><span x-text="error"></span></x-ui.alert></template>

        <div x-show="!loading && !error && items.length === 0" class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-gray-400">
            Belum ada jasa.
        </div>

        <div x-show="!loading && !error && items.length > 0">
            <x-ui.table>
                <x-slot:head>
                    <x-ui.th>Nama Jasa</x-ui.th>
                    <x-ui.th align="right">Harga Default</x-ui.th>
                    <x-ui.th align="center">Status</x-ui.th>
                    <x-ui.th align="right">Aksi</x-ui.th>
                </x-slot:head>
                <template x-for="s in items" :key="s.id">
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-1">
                            <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-warning-50 text-warning-600"><x-heroicon-o-wrench-screwdriver class="h-5 w-5" /></span>
                                <span class="text-sm font-medium text-gray-800" x-text="s.name"></span>
                            </div>
                        </td>
                        <td class="px-4 py-1 text-right text-sm font-medium text-gray-800" x-text="window.rupiah(s.default_price)"></td>
                        <td class="px-4 py-1 text-center">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset"
                                :class="s.is_active ? 'bg-primary-50 text-primary-700 ring-primary-600/20' : 'bg-gray-100 text-gray-500 ring-gray-500/20'"
                                x-text="s.is_active ? 'Aktif' : 'Nonaktif'"></span>
                        </td>
                        <td class="px-4 py-1">
                            <div class="flex items-center justify-end gap-1">
                                <button type="button" @click="openEdit(s)" class="rounded-md p-1.5 text-gray-400 transition hover:bg-primary-50 hover:text-primary-600" title="Edit"><x-heroicon-o-pencil-square class="h-4 w-4" /></button>
                                <button type="button" @click="askDelete(s)" class="rounded-md p-1.5 text-gray-400 transition hover:bg-danger-50 hover:text-danger-600" title="Hapus"><x-heroicon-o-trash class="h-4 w-4" /></button>
                            </div>
                        </td>
                    </tr>
                </template>
                <x-slot:footer><x-ui.pagination page="meta.current_page" lastPage="meta.last_page" total="meta.total" /></x-slot:footer>
            </x-ui.table>
        </div>

        {{-- Modal form jasa --}}
        <x-ui.modal size="md" show="showForm" title="Form Jasa">
            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Nama Jasa <span class="text-danger-500">*</span></label>
                    <input type="text" x-model="form.name" placeholder="cth. Instalasi Jaringan LAN"
                        class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" :class="err('name') && 'border-danger-400'" />
                    <p x-show="err('name')" x-text="err('name')" class="mt-1 text-xs text-danger-600"></p>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Harga Default</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-gray-400">Rp</span>
                        <input type="number" min="0" x-model.number="form.default_price"
                            class="block w-full rounded-lg border-gray-300 pl-9 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Deskripsi</label>
                    <textarea rows="2" x-model="form.description" placeholder="Opsional"
                        class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
                </div>
                <label class="flex items-center gap-3">
                    <input type="checkbox" x-model="form.is_active" class="rounded text-primary-600 focus:ring-primary-500" />
                    <span class="text-sm text-gray-700">Jasa aktif</span>
                </label>
            </div>
            <x-slot:footer>
                <x-ui.button variant="outline" type="button" @click="showForm = false">Batal</x-ui.button>
                <x-ui.button type="button" icon="check" ::disabled="saving || !form.name" @click="submit()">
                    <span x-text="saving ? 'Menyimpan...' : 'Simpan'"></span>
                </x-ui.button>
            </x-slot:footer>
        </x-ui.modal>

        <x-ui.modal title="Hapus Jasa" size="sm" show="showDelete">
            <p class="text-sm text-gray-600">Yakin menghapus jasa <span class="font-semibold" x-text="deleting?.name"></span>?</p>
            <x-slot:footer>
                <x-ui.button variant="outline" type="button" @click="showDelete = false">Batal</x-ui.button>
                <x-ui.button variant="danger" type="button" icon="trash" @click="doDelete()">Hapus</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    </div>
</x-layouts.app>
