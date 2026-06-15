<x-layouts.app title="Produk">
    <div
        x-data="{
            loading: true,
            error: null,
            items: [],
            meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 },
            search: '',
            brand: '',
            brands: [],
            category: '',
            categories: [],
            page: 1,
            deleting: null,
            showDelete: false,

            showImport: false,
            importing: false,
            importFile: null,
            importResult: null,
            openImport() { this.importFile = null; this.importResult = null; this.showImport = true; },
            onImportFile(e) { this.importFile = e.target.files[0] || null; },
            downloadTemplate() { window.location.href = '/api/products/import-template'; },
            async doImport() {
                if (! this.importFile) { this.$store.toasts.error('Pilih file Excel dulu.'); return; }
                this.importing = true; this.importResult = null;
                const fd = new FormData();
                fd.append('file', this.importFile);
                try {
                    const res = await window.api.post('/api/products/import', fd);
                    this.importResult = res.data;
                    this.$store.toasts.success(res.message);
                    this.load();
                    this.loadBrands();
                } catch (e) {
                    this.$store.toasts.error(e.errors?.file?.[0] || e.message);
                } finally {
                    this.importing = false;
                }
            },

            async load() {
                this.loading = true;
                this.error = null;
                try {
                    const params = new URLSearchParams({ page: this.page, per_page: 10 });
                    if (this.search) params.set('search', this.search);
                    if (this.brand) params.set('brand', this.brand);
                    if (this.category) params.set('category_id', this.category);
                    const res = await window.api.get('/api/products?' + params.toString());
                    this.items = res.data;
                    this.meta = res.meta;
                } catch (e) {
                    this.error = e.message;
                } finally {
                    this.loading = false;
                }
            },
            async loadBrands() {
                try {
                    const res = await window.api.get('/api/products?per_page=200');
                    this.brands = [...new Set(res.data.map((p) => p.brand).filter(Boolean))].sort();
                } catch (e) { /* abaikan */ }
            },
            async loadCategories() {
                try {
                    const res = await window.api.get('/api/categories?all=1');
                    this.categories = res.data;
                } catch (e) { /* abaikan */ }
            },
            goToPage(p) {
                if (p < 1 || p > this.meta.last_page) return;
                this.page = p;
                this.load();
            },
            askDelete(item) { this.deleting = item; this.showDelete = true; },
            async doDelete() {
                try {
                    const res = await window.api.delete('/api/products/' + this.deleting.id);
                    this.$store.toasts.success(res.message || 'Produk dihapus');
                    this.showDelete = false;
                    if (this.items.length === 1 && this.page > 1) this.page--;
                    this.load();
                } catch (e) {
                    this.$store.toasts.error(e.message);
                }
            },
        }"
        x-init="$nextTick(() => { const f = window.flash.pop(); if (f) $store.toasts.push(f.message, f.type); }); load(); loadBrands(); loadCategories(); $watch('search', () => { page = 1; load(); }); $watch('brand', () => { page = 1; load(); }); $watch('category', () => { page = 1; load(); })"
    >
        {{-- Toolbar --}}
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-1 flex-col gap-2 sm:flex-row sm:items-center">
                <div class="sm:w-72">
                    <x-ui.search-input placeholder="Cari nama atau SKU produk..." model="search" />
                </div>
                <select x-model="category" class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:w-48">
                    <option value="">Semua Kategori</option>
                    <template x-for="c in categories" :key="c.id"><option :value="c.id" x-text="c.name"></option></template>
                </select>
                <select x-model="brand" class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:w-48">
                    <option value="">Semua Merek</option>
                    <template x-for="b in brands" :key="b"><option :value="b" x-text="b"></option></template>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <x-ui.button variant="outline" type="button" icon="arrow-up-tray" @click="openImport()">Import Excel</x-ui.button>
                <x-ui.button :href="route('products.create')" icon="plus">Tambah Produk</x-ui.button>
            </div>
        </div>

        <div x-show="loading" class="py-10"><x-ui.loading-spinner size="lg" label="Memuat produk..." /></div>

        <template x-if="error">
            <x-ui.alert variant="danger" title="Gagal memuat"><span x-text="error"></span></x-ui.alert>
        </template>

        <div x-show="!loading && !error && items.length === 0" class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-gray-400">
            Tidak ada produk yang cocok.
        </div>

        <div x-show="!loading && !error && items.length > 0">
            <x-ui.table>
                <x-slot:head>
                    <x-ui.th>Produk</x-ui.th>
                    <x-ui.th>Kategori</x-ui.th>
                    <x-ui.th>Merek</x-ui.th>
                    @can('products.view-cost')
                        <x-ui.th align="right">Modal</x-ui.th>
                    @endcan
                    <x-ui.th>Harga per Tipe</x-ui.th>
                    <x-ui.th align="center">Stok</x-ui.th>
                    <x-ui.th align="right">Aksi</x-ui.th>
                </x-slot:head>

                <template x-for="p in items" :key="p.id">
                    {{-- Baris produk nonaktif diredupkan (pengganti kolom status). --}}
                    <tr class="hover:bg-gray-50" :class="{ 'opacity-50': !p.is_active }">
                        <td class="px-4 py-1">
                            <div class="flex items-center gap-3">
                                <template x-if="p.image_url">
                                    <img :src="p.image_url" :alt="p.name" class="h-9 w-9 flex-shrink-0 rounded-lg object-cover" />
                                </template>
                                <template x-if="!p.image_url">
                                    <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-400"><x-heroicon-o-cube class="h-5 w-5" /></span>
                                </template>
                                <span class="text-sm font-medium text-gray-800" x-text="p.name"></span>
                            </div>
                        </td>
                        <td class="px-4 py-1">
                            <span x-show="p.category_name" class="inline-flex items-center rounded-full bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-700 ring-1 ring-inset ring-primary-600/20" x-text="p.category_name"></span>
                            <span x-show="!p.category_name" class="text-sm text-gray-400">—</span>
                        </td>
                        <td class="px-4 py-1 text-sm text-gray-500" x-text="p.brand || '—'"></td>
                        @can('products.view-cost')
                            <td class="whitespace-nowrap px-4 py-1 text-right text-sm text-gray-600" x-text="window.rupiah(p.purchase_price)"></td>
                        @endcan
                        <td class="whitespace-nowrap px-4 py-1">
                            <div class="flex flex-col gap-0.5">
                                <template x-for="pr in p.prices" :key="pr.price_type">
                                    {{-- Harga default disorot dengan teks primary tebal (tanpa tag). --}}
                                    <div class="flex items-center gap-1.5 text-xs">
                                        <span x-text="(pr.price_type_name || pr.price_type) + ':'"
                                            :class="pr.is_active_default ? 'font-semibold text-primary-600' : 'text-gray-400'"></span>
                                        <span x-text="window.rupiah(pr.price)"
                                            :class="pr.is_active_default ? 'font-bold text-primary-700' : 'font-medium text-gray-700'"></span>
                                    </div>
                                </template>
                                <span x-show="!p.prices || p.prices.length === 0" class="text-sm text-gray-400">—</span>
                            </div>
                        </td>
                        <td class="px-4 py-1 text-center">
                            <span class="inline-flex min-w-9 justify-center rounded-md px-2 py-0.5 text-xs font-medium"
                                :class="p.is_low_stock ? 'bg-danger-50 text-danger-700' : 'bg-gray-100 text-gray-600'" x-text="p.stock"></span>
                        </td>
                        <td class="px-4 py-1">
                            <div class="flex items-center justify-end gap-1">
                                <a :href="'{{ url('products') }}/' + p.id + '/edit'" class="rounded-md p-1.5 text-gray-400 transition hover:bg-primary-50 hover:text-primary-600" title="Edit"><x-heroicon-o-pencil-square class="h-4 w-4" /></a>
                                <button type="button" @click="askDelete(p)" class="rounded-md p-1.5 text-gray-400 transition hover:bg-danger-50 hover:text-danger-600" title="Hapus"><x-heroicon-o-trash class="h-4 w-4" /></button>
                            </div>
                        </td>
                    </tr>
                </template>

                <x-slot:footer>
                    <x-ui.pagination page="meta.current_page" lastPage="meta.last_page" total="meta.total" />
                </x-slot:footer>
            </x-ui.table>
        </div>

        {{-- Konfirmasi hapus --}}
        <x-ui.modal title="Hapus Produk" size="sm" show="showDelete">
            <p class="text-sm text-gray-600">Yakin menghapus produk <span class="font-semibold" x-text="deleting?.name"></span>? Tindakan ini tidak dapat dibatalkan.</p>
            <x-slot:footer>
                <x-ui.button variant="outline" type="button" @click="showDelete = false">Batal</x-ui.button>
                <x-ui.button variant="danger" type="button" icon="trash" @click="doDelete()">Hapus</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>

        {{-- Import Excel --}}
        <x-ui.modal title="Import Produk dari Excel" size="lg" show="showImport">
            <div class="space-y-4">
                <x-ui.alert variant="info">
                    Satu baris = satu produk. Kolom <b>name</b> wajib; <b>sku</b> dipakai sebagai kunci (produk dengan SKU sama akan diperbarui).
                    Kolom tipe harga (mis. <b>umum, teknisi, grosir</b>) berada di sisi kanan sesuai master Tipe Harga.
                    Unduh template agar kolom sesuai.
                </x-ui.alert>

                <div>
                    <x-ui.button variant="outline" type="button" icon="arrow-down-tray" @click="downloadTemplate()">Unduh Template</x-ui.button>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">File Excel (.xlsx / .xls / .csv)</label>
                    <input type="file" accept=".xlsx,.xls,.csv" @change="onImportFile($event)"
                        class="block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-primary-700 hover:file:bg-primary-100" />
                </div>

                {{-- Ringkasan hasil --}}
                <template x-if="importResult">
                    <div class="rounded-lg border border-gray-200 p-3 text-sm">
                        <div class="flex flex-wrap gap-3">
                            <span class="inline-flex items-center gap-1 text-primary-700"><x-heroicon-o-check-circle class="h-4 w-4" /> <span x-text="importResult.created"></span> ditambahkan</span>
                            <span class="inline-flex items-center gap-1 text-gray-600"><x-heroicon-o-arrow-path class="h-4 w-4" /> <span x-text="importResult.updated"></span> diperbarui</span>
                            <span class="inline-flex items-center gap-1 text-danger-600" x-show="importResult.failed.length"><x-heroicon-o-x-circle class="h-4 w-4" /> <span x-text="importResult.failed.length"></span> gagal</span>
                        </div>
                        <div x-show="importResult.failed.length" class="mt-2 max-h-40 overflow-y-auto border-t border-gray-100 pt-2 scrollbar-thin">
                            <template x-for="f in importResult.failed" :key="f.row">
                                <p class="text-xs text-danger-600">Baris <span x-text="f.row"></span>: <span x-text="f.message"></span></p>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <x-slot:footer>
                <x-ui.button variant="outline" type="button" @click="showImport = false">Tutup</x-ui.button>
                <x-ui.button type="button" icon="arrow-up-tray" ::disabled="importing || !importFile" @click="doImport()">
                    <span x-text="importing ? 'Mengimpor...' : 'Import'"></span>
                </x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    </div>
</x-layouts.app>
