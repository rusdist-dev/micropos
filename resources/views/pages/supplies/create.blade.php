<x-layouts.app title="Supply Baru" :breadcrumbs="['Supply Barang' => route('supplies.index'), 'Baru' => null]">
    <div
        x-data="{
            loading: true, saving: false, loadError: null,
            suppliers: [], productResults: [], priceTypes: [],
            supplierId: '', note: '',
            productSearch: '', showPicker: false, searchingProducts: false,
            lines: [],   // {key, product_id, name, qty, purchase_price, currentCost, prices:{code:val}}

            async init() {
                try {
                    const [sup, pt] = await Promise.all([
                        window.api.get('/api/suppliers?all=1&is_active=1'),
                        window.api.get('/api/price-types?all=1&is_active=1'),
                    ]);
                    this.suppliers = sup.data;
                    this.priceTypes = pt.data.map(t => ({ code: t.code, name: t.name }));
                } catch (e) { this.loadError = e.message; } finally { this.loading = false; }
            },
            // Pencarian produk server-side agar ringan walau produk sangat banyak.
            async searchProducts() {
                this.showPicker = true;
                this.searchingProducts = true;
                try {
                    const params = new URLSearchParams({ per_page: 8 });
                    if (this.productSearch) params.set('search', this.productSearch);
                    const res = await window.api.get('/api/products?' + params.toString());
                    this.productResults = res.data.map(p => ({
                        id: p.id, name: p.name, sku: p.sku, purchase_price: p.purchase_price,
                        prices: Object.fromEntries((p.prices || []).map(r => [r.price_type, r.price])),
                    }));
                } catch (e) { this.$store.toasts.error(e.message); } finally { this.searchingProducts = false; }
            },
            get filteredProducts() {
                const inCart = this.lines.map(l => l.product_id);
                return this.productResults.filter(p => !inCart.includes(p.id));
            },
            addProduct(p) {
                const prices = {};
                this.priceTypes.forEach(t => { prices[t.code] = p.prices[t.code] ?? ''; });
                this.lines.push({ key: 'l-' + p.id, product_id: p.id, name: p.name, qty: 1,
                    purchase_price: p.purchase_price, currentCost: p.purchase_price, prices });
                this.productSearch = ''; this.productResults = []; this.showPicker = false;
            },
            removeLine(i) { this.lines.splice(i, 1); },
            lineCost(l) { return Number(l.qty || 0) * Number(l.purchase_price || 0); },
            get totalCost() { return this.lines.reduce((s, l) => s + this.lineCost(l), 0); },

            async submit() {
                if (this.lines.length === 0) { this.$store.toasts.error('Tambahkan minimal satu produk.'); return; }
                if (!this.supplierId) { this.$store.toasts.error('Pilih pemasok.'); return; }
                this.saving = true;
                const items = this.lines.map(l => ({
                    product_id: l.product_id,
                    qty: Number(l.qty),
                    purchase_price: l.purchase_price === '' ? null : Number(l.purchase_price),
                    prices: this.priceTypes
                        .filter(t => l.prices[t.code] !== '' && l.prices[t.code] !== null && l.prices[t.code] !== undefined)
                        .map(t => ({ price_type: t.code, price: Number(l.prices[t.code]) })),
                }));
                try {
                    const res = await window.api.post('/api/supplies', { supplier_id: this.supplierId, note: this.note, items });
                    window.flash.set(res.message || 'Supply disimpan', 'success');
                    window.location.href = '{{ route('supplies.index') }}';
                } catch (e) {
                    this.saving = false;
                    this.$store.toasts.error(e.status === 422 ? (e.message || 'Periksa isian.') : e.message);
                }
            },
        }"
    >
        <template x-if="loadError"><x-ui.alert variant="danger" title="Gagal memuat data"><span x-text="loadError"></span></x-ui.alert></template>
        <div x-show="loading" class="py-10"><x-ui.loading-spinner size="lg" label="Memuat..." /></div>

        <div x-show="!loading" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                {{-- Pemasok & tambah produk --}}
                <x-ui.card title="Informasi Supply">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Pemasok <span class="text-danger-500">*</span></label>
                            <select x-model="supplierId" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                <option value="">Pilih pemasok</option>
                                <template x-for="s in suppliers" :key="s.id"><option :value="s.id" x-text="s.name"></option></template>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Catatan</label>
                            <input type="text" x-model="note" placeholder="Opsional" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                        </div>
                    </div>

                    <div class="relative mt-4" @click.outside="showPicker = false">
                        <label class="mb-1 block text-sm font-medium text-gray-700">Tambah Produk</label>
                        <input type="text" x-model="productSearch" @focus="searchProducts()" @input.debounce.300ms="searchProducts()" placeholder="Cari produk (nama/SKU)..."
                            class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                        <div x-show="showPicker && filteredProducts.length" x-cloak class="absolute z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg scrollbar-thin">
                            <template x-for="p in filteredProducts" :key="p.id">
                                <button type="button" @click="addProduct(p)" class="flex w-full items-center justify-between px-3 py-2 text-left text-sm hover:bg-primary-50">
                                    <span x-text="p.name"></span>
                                    <span class="text-xs text-gray-400" x-text="'Modal: ' + window.rupiah(p.purchase_price)"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </x-ui.card>

                {{-- Baris item --}}
                <div x-show="lines.length === 0" class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-gray-400">Belum ada produk. Cari & tambahkan di atas.</div>

                <template x-for="(l, i) in lines" :key="l.key">
                    <x-ui.card>
                        <div class="mb-3 flex items-start justify-between">
                            <div>
                                <p class="text-sm font-semibold text-gray-800" x-text="l.name"></p>
                                <p class="text-xs text-gray-400">Modal saat ini: <span x-text="window.rupiah(l.currentCost)"></span></p>
                            </div>
                            <button type="button" @click="removeLine(i)" class="rounded p-1 text-gray-300 transition hover:bg-danger-50 hover:text-danger-600"><x-heroicon-o-x-mark class="h-5 w-5" /></button>
                        </div>
                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-500">Qty Masuk</label>
                                <input type="number" min="1" x-model.number="l.qty" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-500">Harga Modal Baru</label>
                                <input type="number" min="0" x-model.number="l.purchase_price" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                            </div>
                            <div class="flex items-end">
                                <p class="text-sm text-gray-500">Subtotal: <span class="font-semibold text-gray-800" x-text="window.rupiah(lineCost(l))"></span></p>
                            </div>
                        </div>
                        {{-- Harga jual per tipe --}}
                        <div class="mt-3 border-t border-gray-100 pt-3">
                            <p class="mb-2 text-xs font-medium text-gray-500">Update Harga Jual (opsional)</p>
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                <template x-for="t in priceTypes" :key="t.code">
                                    <div>
                                        <label class="mb-1 block text-[11px] text-gray-400" x-text="t.name"></label>
                                        <input type="number" min="0" x-model.number="l.prices[t.code]" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                                    </div>
                                </template>
                            </div>
                        </div>
                    </x-ui.card>
                </template>
            </div>

            {{-- Ringkasan --}}
            <div class="space-y-6">
                <x-ui.card title="Ringkasan">
                    <div class="flex items-center justify-between text-lg">
                        <span class="font-medium text-gray-600">Total Modal</span>
                        <span class="font-bold text-gray-900" x-text="window.rupiah(totalCost)"></span>
                    </div>
                    <p class="mt-1 text-xs text-gray-400"><span x-text="lines.length"></span> produk</p>
                    <div class="mt-4 space-y-2">
                        <x-ui.button type="button" class="w-full" icon="check" ::disabled="saving || lines.length === 0 || !supplierId" @click="submit()">
                            <span x-text="saving ? 'Menyimpan...' : 'Simpan Supply'"></span>
                        </x-ui.button>
                        <x-ui.button variant="outline" :href="route('supplies.index')" class="w-full">Batal</x-ui.button>
                    </div>
                </x-ui.card>
            </div>
        </div>
    </div>
</x-layouts.app>
