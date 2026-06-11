<x-layouts.app title="Retur Baru" :breadcrumbs="['Retur Barang' => route('returns.index'), 'Baru' => null]">
    <div
        x-data="{
            loadingMeta: true, processing: false,
            products: [], priceTypes: [],

            invoiceQuery: '', searching: false, results: [], showResults: false,
            original: null,            // { id, invoice_number, customer_name }
            returnLines: [],           // {transaction_item_id, product_id, item_name, price_snapshot, max_qty, qty, restock}

            exchangeSearch: '', showPicker: false,
            exchangeLines: [],         // {key, product_id, name, price_type, availTypes[], price, prices{}, qty, stock}
            paymentAmount: '',

            async init() {
                try {
                    const [prod, pt] = await Promise.all([
                        window.api.get('/api/products?is_active=1&per_page=500'),
                        window.api.get('/api/price-types?all=1&is_active=1'),
                    ]);
                    this.priceTypes = pt.data.map(t => ({ code: t.code, name: t.name }));
                    this.products = prod.data.map(p => ({
                        id: p.id, name: p.name, sku: p.sku, stock: p.stock,
                        prices: Object.fromEntries((p.prices || []).map(r => [r.price_type, r.price])),
                    }));
                } catch (e) { this.$store.toasts.error('Gagal memuat data: ' + e.message); } finally { this.loadingMeta = false; }
            },

            // --- Cari invoice ---
            async findInvoice() {
                if (!this.invoiceQuery) { this.results = []; return; }
                this.searching = true;
                try {
                    const res = await window.api.get('/api/transactions?per_page=8&search=' + encodeURIComponent(this.invoiceQuery));
                    this.results = res.data; this.showResults = true;
                } catch (e) { this.$store.toasts.error(e.message); } finally { this.searching = false; }
            },
            async selectTransaction(t) {
                this.showResults = false; this.invoiceQuery = t.invoice_number;
                try {
                    const res = await window.api.get('/api/transactions/' + t.id + '/returnable');
                    this.original = res.data.transaction;
                    this.returnLines = res.data.items.map(it => ({
                        transaction_item_id: it.transaction_item_id, product_id: it.product_id,
                        item_name: it.item_name, price_snapshot: it.price_snapshot,
                        max_qty: it.remaining_qty, qty: 0, restock: true,
                    }));
                    this.exchangeLines = []; this.paymentAmount = '';
                } catch (e) { this.$store.toasts.error(e.message); }
            },
            resetInvoice() { this.original = null; this.returnLines = []; this.exchangeLines = []; this.invoiceQuery = ''; this.results = []; },

            clampReturn(l) {
                let q = Math.floor(Number(l.qty || 0));
                if (isNaN(q) || q < 0) q = 0;
                if (q > l.max_qty) q = l.max_qty;
                l.qty = q;
            },

            // --- Penukaran ---
            get filteredProducts() {
                const q = this.exchangeSearch.toLowerCase();
                return this.products.filter(p => !q || p.name.toLowerCase().includes(q) || (p.sku && p.sku.toLowerCase().includes(q))).slice(0, 8);
            },
            availableTypes(p) { return this.priceTypes.filter(t => p.prices[t.code] !== undefined); },
            addExchange(p) {
                const types = this.availableTypes(p);
                if (!types.length) { this.$store.toasts.error(p.name + ' belum punya harga jual.'); return; }
                const t = types[0].code;
                this.exchangeLines.push({ key: 'x-' + Date.now() + '-' + p.id, product_id: p.id, name: p.name,
                    price_type: t, availTypes: types, price: p.prices[t], prices: p.prices, qty: 1, stock: p.stock });
                this.exchangeSearch = ''; this.showPicker = false;
            },
            onTypeChange(l) { l.price = l.prices[l.price_type]; },
            clampExchange(l) {
                let q = Math.floor(Number(l.qty || 1));
                if (isNaN(q) || q < 1) q = 1;
                if (q > l.stock) { q = l.stock; this.$store.toasts.error('Stok ' + l.name + ' hanya ' + l.stock + '.'); }
                l.qty = q;
            },
            removeExchange(i) { this.exchangeLines.splice(i, 1); },

            // --- Total ---
            get returnedTotal() { return this.returnLines.reduce((s, l) => s + l.price_snapshot * Number(l.qty || 0), 0); },
            get exchangeTotal() { return this.exchangeLines.reduce((s, l) => s + l.price * Number(l.qty || 0), 0); },
            get balance() { return this.exchangeTotal - this.returnedTotal; },
            get payable() { return this.balance > 0 ? this.balance : 0; },
            get refund() { return this.balance < 0 ? -this.balance : 0; },
            get hasItems() { return this.returnLines.some(l => l.qty > 0) || this.exchangeLines.length > 0; },

            async submit() {
                if (!this.original) { this.$store.toasts.error('Pilih invoice asal dulu.'); return; }
                if (!this.hasItems) { this.$store.toasts.error('Pilih item retur atau penukaran.'); return; }
                if (this.payable > 0 && (this.paymentAmount === '' || Number(this.paymentAmount) < this.payable)) {
                    this.$store.toasts.error('Pembayaran selisih kurang.'); return;
                }
                this.processing = true;
                const payload = {
                    transaction_id: this.original.id,
                    returned_items: this.returnLines.filter(l => l.qty > 0).map(l => ({ transaction_item_id: l.transaction_item_id, qty: Number(l.qty), restock: l.restock })),
                    exchange_items: this.exchangeLines.map(l => ({ product_id: l.product_id, price_type: l.price_type, qty: Number(l.qty) })),
                    payment_amount: this.payable > 0 ? Number(this.paymentAmount) : 0,
                };
                try {
                    const res = await window.api.post('/api/returns', payload);
                    window.flash.set(res.message || 'Retur berhasil diproses', 'success');
                    window.location.href = '{{ url('returns') }}/' + res.data.id;
                } catch (e) {
                    this.processing = false;
                    this.$store.toasts.error(e.message);
                }
            },
        }"
        x-init="init()"
    >
        {{-- Cari invoice --}}
        <x-ui.card title="Transaksi Asal" class="mb-4">
            <div x-show="!original" class="relative" @click.outside="showResults = false">
                <div class="flex gap-2">
                    <div class="relative flex-1">
                        <input type="text" x-model="invoiceQuery" @keydown.enter.prevent="findInvoice()" placeholder="Cari nomor invoice atau nama pelanggan..."
                            class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                    </div>
                    <x-ui.button type="button" icon="magnifying-glass" ::disabled="searching" @click="findInvoice()">Cari</x-ui.button>
                </div>
                <div x-show="showResults && results.length" x-cloak class="absolute z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg scrollbar-thin">
                    <template x-for="t in results" :key="t.id">
                        <button type="button" @click="selectTransaction(t)" class="flex w-full items-center justify-between px-3 py-2 text-left text-sm hover:bg-primary-50">
                            <span class="font-medium text-primary-700" x-text="t.invoice_number"></span>
                            <span class="text-xs text-gray-400" x-text="(t.customer_name || 'Umum') + ' · ' + window.rupiah(t.total)"></span>
                        </button>
                    </template>
                </div>
                <p x-show="showResults && !results.length" x-cloak class="mt-2 text-sm text-gray-400">Tidak ada transaksi cocok.</p>
            </div>

            <div x-show="original" class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-primary-700" x-text="original?.invoice_number"></p>
                    <p class="text-xs text-gray-500" x-text="original?.customer_name"></p>
                </div>
                <x-ui.button variant="outline" size="sm" type="button" icon="x-mark" @click="resetInvoice()">Ganti</x-ui.button>
            </div>
        </x-ui.card>

        <div x-show="original" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Kiri: item retur + penukaran --}}
            <div class="space-y-6 lg:col-span-2">
                {{-- Item dikembalikan --}}
                <x-ui.card title="Item Dikembalikan">
                    <div x-show="returnLines.length === 0" class="py-4 text-center text-sm text-gray-400">Tidak ada item yang dapat diretur dari invoice ini.</div>
                    <div class="space-y-2" x-show="returnLines.length > 0">
                        <template x-for="l in returnLines" :key="l.transaction_item_id">
                            <div class="rounded-lg border border-gray-200 p-3">
                                <div class="flex items-center justify-between">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-gray-800" x-text="l.item_name"></p>
                                        <p class="text-xs text-gray-400">@ <span x-text="window.rupiah(l.price_snapshot)"></span> · maks <span x-text="l.max_qty"></span></p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <div>
                                            <label class="mb-0.5 block text-[11px] text-gray-400">Qty retur</label>
                                            <input type="number" min="0" :max="l.max_qty" x-model.number="l.qty" @input="clampReturn(l)"
                                                class="w-20 rounded-lg border-gray-300 text-center text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                                        </div>
                                        <label class="flex cursor-pointer flex-col items-center gap-0.5">
                                            <span class="text-[11px] text-gray-400">Restok</span>
                                            <input type="checkbox" x-model="l.restock" class="rounded text-primary-600 focus:ring-primary-500" title="Centang jika barang masih layak jual" />
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <p class="text-xs text-gray-400">Hapus centang <b>Restok</b> untuk barang rusak (tidak dikembalikan ke stok).</p>
                    </div>
                </x-ui.card>

                {{-- Item penukaran --}}
                <x-ui.card title="Item Penukaran (opsional)">
                    <div class="relative mb-3" @click.outside="showPicker = false">
                        <input type="text" x-model="exchangeSearch" @focus="showPicker = true" placeholder="Cari produk untuk ditukar..."
                            class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                        <div x-show="showPicker && filteredProducts.length" x-cloak class="absolute z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg scrollbar-thin">
                            <template x-for="p in filteredProducts" :key="p.id">
                                <button type="button" @click="addExchange(p)" class="flex w-full items-center justify-between px-3 py-2 text-left text-sm hover:bg-primary-50">
                                    <span x-text="p.name"></span>
                                    <span class="text-xs text-gray-400" x-text="'Stok: ' + p.stock"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                    <div x-show="exchangeLines.length === 0" class="py-2 text-center text-sm text-gray-400">Belum ada item penukaran.</div>
                    <div class="space-y-2">
                        <template x-for="(l, i) in exchangeLines" :key="l.key">
                            <div class="rounded-lg border border-gray-200 p-3">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="text-sm font-medium text-gray-800" x-text="l.name"></p>
                                    <button type="button" @click="removeExchange(i)" class="rounded p-1 text-gray-300 hover:bg-danger-50 hover:text-danger-600"><x-heroicon-o-x-mark class="h-4 w-4" /></button>
                                </div>
                                <div class="mt-2 grid grid-cols-3 gap-2">
                                    <div>
                                        <label class="mb-0.5 block text-[11px] text-gray-400">Tipe Harga</label>
                                        <select x-model="l.price_type" @change="onTypeChange(l)" class="block w-full rounded-lg border-gray-300 py-1 text-xs shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                            <template x-for="t in l.availTypes" :key="t.code"><option :value="t.code" x-text="t.name"></option></template>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="mb-0.5 block text-[11px] text-gray-400">Qty</label>
                                        <input type="number" min="1" :max="l.stock" x-model.number="l.qty" @input="clampExchange(l)" class="block w-full rounded-lg border-gray-300 py-1 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                                    </div>
                                    <div class="flex items-end justify-end">
                                        <span class="text-sm font-semibold text-gray-800" x-text="window.rupiah(l.price * l.qty)"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </x-ui.card>
            </div>

            {{-- Kanan: ringkasan saldo --}}
            <div class="space-y-6">
                <x-ui.card title="Ringkasan">
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Nilai Dikembalikan</dt><dd class="text-gray-800" x-text="window.rupiah(returnedTotal)"></dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Nilai Penukaran</dt><dd class="text-gray-800" x-text="window.rupiah(exchangeTotal)"></dd></div>
                        <div class="flex justify-between border-t border-gray-100 pt-2 text-base font-semibold">
                            <dt class="text-gray-700" x-text="balance >= 0 ? 'Tagihan Pelanggan' : 'Dikembalikan ke Pelanggan'"></dt>
                            <dd :class="balance > 0 ? 'text-gray-900' : (balance < 0 ? 'text-danger-600' : 'text-gray-500')" x-text="window.rupiah(Math.abs(balance))"></dd>
                        </div>
                    </dl>

                    <div x-show="payable > 0" class="mt-4">
                        <label class="mb-1 block text-sm font-medium text-gray-700">Jumlah Bayar</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">Rp</span>
                            <input type="number" min="0" x-model.number="paymentAmount" class="block w-full rounded-lg border-gray-300 pl-10 text-sm font-semibold shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                        </div>
                        <p class="mt-1 text-xs" :class="paymentAmount !== '' && Number(paymentAmount) >= payable ? 'text-gray-400' : 'text-danger-600'">
                            Kembalian: <span x-text="window.rupiah(Math.max(0, Number(paymentAmount || 0) - payable))"></span>
                        </p>
                    </div>

                    <div x-show="refund > 0" class="mt-4 rounded-lg bg-danger-50 p-3 text-sm text-danger-700">
                        Kembalikan uang ke pelanggan sebesar <span class="font-semibold" x-text="window.rupiah(refund)"></span>.
                    </div>

                    <div class="mt-4 space-y-2">
                        <x-ui.button type="button" class="w-full" icon="check" ::disabled="processing || !hasItems" @click="submit()">
                            <span x-text="processing ? 'Memproses...' : 'Proses Retur'"></span>
                        </x-ui.button>
                        <x-ui.button variant="outline" :href="route('returns.index')" class="w-full">Batal</x-ui.button>
                    </div>
                </x-ui.card>
            </div>
        </div>
    </div>
</x-layouts.app>
