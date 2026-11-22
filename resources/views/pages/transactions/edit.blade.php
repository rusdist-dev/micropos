<x-layouts.app title="Edit Transaksi" :breadcrumbs="['Riwayat' => route('transactions.index'), 'Detail' => route('transactions.show', $id), 'Edit' => null]">
    <div
        x-data="{
            id: {{ (int) $id }},
            loading: true,
            saving: false,
            error: null,
            errors: {},
            trx: null,
            customers: [],
            priceTypes: [],
            items: [],
            customerId: '',
            discount: 0,
            payment: 0,
            note: '',
            seq: 1,
            search: '',
            results: [],
            searching: false,
            appliedPriceType: '', // tipe harga yang sedang diterapkan pada item
            priceType: '',      // tipe harga transaksi (berlaku untuk semua item produk, seperti di kasir)

            async init() {
                try {
                    const [t, c, pt] = await Promise.all([
                        window.api.get('/api/transactions/' + this.id),
                        window.api.get('/api/customers?per_page=500'),
                        window.api.get('/api/price-types?all=1&is_active=1'),
                    ]);
                    this.customers = c.data.map((x) => ({ id: x.id, name: x.name }));
                    this.priceTypes = pt.data.map((x) => ({ code: x.code, name: x.name }));
                    this.trx = t.data;
                    this.customerId = t.data.customer_id ?? '';
                    this.discount = t.data.discount;
                    this.payment = t.data.payment_amount;
                    this.note = t.data.note ?? '';
                    // Tipe harga awal = tipe harga yang dipakai item produk transaksi ini.
                    this.priceType = t.data.items.find((i) => i.item_type === 'product' && i.price_type_used)?.price_type_used
                        ?? this.priceTypes[0]?.code ?? '';
                    this.appliedPriceType = this.priceType;
                    this.items = t.data.items.map((i) => ({
                        key: 'i-' + (this.seq++), type: i.item_type, product_id: i.product_id, service_id: i.service_id,
                        name: i.item_name, price_type: i.price_type_used, price: i.price_snapshot, qty: i.qty, note: i.note,
                        isNew: false,
                    }));
                } catch (e) { this.error = e.message; } finally { this.loading = false; }
            },

            get subtotal() { return this.items.reduce((s, i) => s + Number(i.price || 0) * Number(i.qty || 0), 0); },
            get totalQty() { return this.items.reduce((s, i) => s + (Number(i.qty) || 0), 0); },
            get discountValue() { return Math.min(Math.max(Number(this.discount) || 0, 0), this.subtotal); },
            get total() { return this.subtotal - this.discountValue; },
            get change() { return Number(this.payment || 0) - this.total; },

            async searchProducts() {
                if (this.search.length < 2) { this.results = []; return; }
                this.searching = true;
                try {
                    const res = await window.api.get('/api/products?is_active=1&per_page=8&search=' + encodeURIComponent(this.search));
                    this.results = res.data;
                } catch (e) { this.results = []; } finally { this.searching = false; }
            },
            addProduct(p) {
                const code = this.priceType;
                const row = (p.prices || []).find((r) => r.price_type === code);
                if (! row) { this.$store.toasts.push(p.name + ' tidak punya harga ' + code + '.', 'danger'); return; }
                const existing = this.items.find((i) => i.type === 'product' && i.product_id === p.id && i.price_type === code);
                if (existing) existing.qty++;
                else this.items.push({ key: 'i-' + (this.seq++), type: 'product', product_id: p.id, service_id: null, name: p.name, price_type: code, price: row.price, qty: 1, note: null, isNew: true });
                this.search = ''; this.results = [];
            },
            addService() {
                this.items.push({ key: 'i-' + (this.seq++), type: 'service', product_id: null, service_id: null, name: '', price_type: null, price: 0, qty: 1, note: null, isNew: true });
            },
            // Ganti tipe harga transaksi: seluruh item produk dihitung ulang dengan harga produk saat ini.
            // Bila ada produk tanpa harga untuk tipe tsb, perubahan dibatalkan (tidak ada yang diubah).
            async changePriceType(newType) {
                const oldType = this.appliedPriceType;
                if (newType === oldType) return;
                const products = this.items.filter((i) => i.type === 'product');
                try {
                    const fetched = await Promise.all(products.map((i) => window.api.get('/api/products/' + i.product_id)));
                    const prices = [];
                    const missing = [];
                    products.forEach((i, n) => {
                        const row = (fetched[n].data.prices || []).find((r) => r.price_type === newType);
                        if (row) prices.push(row.price); else missing.push(i.name);
                    });
                    if (missing.length) {
                        this.$store.toasts.push('Tidak punya harga ' + newType + ': ' + missing.join(', '), 'danger');
                        this.priceType = oldType;
                        return;
                    }
                    products.forEach((i, n) => { i.price_type = newType; i.price = prices[n]; });
                    this.appliedPriceType = newType;
                } catch (e) { this.$store.toasts.push(e.message, 'danger'); this.priceType = oldType; }
            },
            removeItem(idx) { this.items.splice(idx, 1); },
            clampQty(item) { const q = Math.floor(Number(item.qty)); item.qty = isNaN(q) || q < 1 ? 1 : q; },

            err(f) { return this.errors[f]?.[0] ?? null; },
            async save() {
                if (this.items.length === 0) { this.$store.toasts.push('Minimal satu item.', 'danger'); return; }
                this.saving = true; this.errors = {};
                const items = this.items.map((i) => i.type === 'product'
                    ? { item_type: 'product', product_id: i.product_id, price_type: i.price_type, qty: i.qty, note: i.note }
                    : { item_type: 'service', service_id: i.service_id, item_name: i.name, price: Number(i.price), qty: i.qty, note: i.note });
                try {
                    const res = await window.api.put('/api/transactions/' + this.id, {
                        customer_id: this.customerId || null,
                        discount: this.discountValue,
                        payment_amount: Number(this.payment),
                        note: this.note || null,
                        items,
                    });
                    window.flash.set(res.message || 'Transaksi diperbarui', 'success');
                    window.location.href = '{{ route('transactions.show', $id) }}';
                } catch (e) {
                    this.saving = false;
                    if (e.status === 422) {
                        this.errors = e.errors || {};
                        this.$store.toasts.push(Object.values(this.errors).flat()[0] || e.message, 'danger');
                    } else this.$store.toasts.push(e.message, 'danger');
                }
            },
        }"
    >
        <div x-show="loading" class="py-10"><x-ui.loading-spinner size="lg" label="Memuat transaksi..." /></div>
        <template x-if="error"><x-ui.alert variant="danger" title="Gagal memuat"><span x-text="error"></span></x-ui.alert></template>

        <form x-show="!loading && trx" x-cloak @submit.prevent="save()" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <x-ui.card title="Item Transaksi">
                    <x-slot:actions>
                        <span class="inline-flex items-center rounded-full bg-primary-50 px-2.5 py-0.5 text-xs font-medium text-primary-700 ring-1 ring-inset ring-primary-600/20" x-text="trx?.invoice_number"></span>
                    </x-slot:actions>

                    <x-ui.alert variant="warning" title="Stok akan disesuaikan">
                        Stok item lama dikembalikan, lalu stok item baru dikurangi. Transaksi yang sudah diretur tidak dapat diedit.
                    </x-ui.alert>

                    {{-- Cari & tambah produk --}}
                    <div class="relative mt-4">
                        <input type="text" x-model="search" @input.debounce.300ms="searchProducts()" placeholder="Cari produk untuk ditambahkan..."
                            class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                        <div x-show="results.length" x-cloak @click.outside="results = []" class="absolute z-20 mt-1 w-full overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg">
                            <template x-for="p in results" :key="p.id">
                                <button type="button" @click="addProduct(p)" :disabled="p.stock <= 0"
                                    class="flex w-full items-center justify-between px-3 py-2 text-left text-sm hover:bg-gray-50 disabled:opacity-40">
                                    <span x-text="p.name"></span>
                                    <span class="text-xs text-gray-400" x-text="'Stok ' + p.stock"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <div class="mt-4 space-y-3">
                        <template x-for="(item, idx) in items" :key="item.key">
                            <div class="rounded-lg border border-gray-200 p-3">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0 flex-1">
                                        <template x-if="item.type === 'product'">
                                            <p class="truncate text-sm font-medium text-gray-800" x-text="item.name"></p>
                                        </template>
                                        <template x-if="item.type === 'service'">
                                            <input type="text" x-model="item.name" placeholder="Nama jasa" class="block w-full rounded-lg border-gray-300 text-sm" />
                                        </template>
                                    </div>
                                    <button type="button" @click="removeItem(idx)" class="rounded p-1 text-gray-300 transition hover:bg-danger-50 hover:text-danger-600">
                                        <x-heroicon-o-x-mark class="h-4 w-4" />
                                    </button>
                                </div>
                                <div class="mt-2 grid grid-cols-2 items-center gap-2 sm:grid-cols-4">
                                    <template x-if="item.type === 'product'">
                                        <span class="text-xs capitalize text-gray-500" x-text="item.price_type"></span>
                                    </template>
                                    <template x-if="item.type === 'service'">
                                        <input type="number" min="0" step="any" x-model.number="item.price" placeholder="Harga" class="block w-full rounded-lg border-gray-300 text-sm" />
                                    </template>
                                    <input type="number" min="1" x-model.number="item.qty" @input="clampQty(item)" class="block w-full rounded-lg border-gray-300 text-center text-sm" />
                                    <span class="text-xs text-gray-400 sm:text-right" x-text="'@ ' + window.rupiah(item.price)"></span>
                                    <span class="text-right text-sm font-semibold text-gray-800" x-text="window.rupiah(Number(item.price) * Number(item.qty))"></span>
                                </div>
                            </div>
                        </template>
                        <p x-show="items.length === 0" class="py-6 text-center text-sm text-gray-400">Belum ada item.</p>
                    </div>

                    <button type="button" @click="addService()" class="mt-3 flex w-full items-center justify-center gap-2 rounded-lg border border-dashed border-warning-300 py-2 text-sm font-medium text-warning-700 transition hover:bg-warning-50">
                        <x-heroicon-o-wrench-screwdriver class="h-4 w-4" />
                        Tambah Jasa
                    </button>
                </x-ui.card>
            </div>

            <div class="space-y-6">
                <x-ui.card title="Informasi">
                    <div class="space-y-3">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-500">Pelanggan</label>
                            <select x-model="customerId" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                <option value="">Pelanggan Umum (Walk-in)</option>
                                <template x-for="c in customers" :key="c.id"><option :value="c.id" x-text="c.name"></option></template>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-500">Tipe Harga</label>
                            <select x-model="priceType" @change="changePriceType($event.target.value)"
                                class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                                <template x-for="t in priceTypes" :key="t.code"><option :value="t.code" x-text="t.name"></option></template>
                            </select>
                            <p class="mt-1 text-[11px] text-gray-400">Mengubah tipe harga menghitung ulang semua item produk dengan harga produk saat ini.</p>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-500">Catatan</label>
                            <textarea x-model="note" rows="2" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card title="Pembayaran">
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between text-gray-500"><span>Total Qty</span><span x-text="totalQty + ' item'"></span></div>
                        <div class="flex justify-between text-gray-500"><span>Subtotal</span><span x-text="window.rupiah(subtotal)"></span></div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-500">Diskon (Rp)</label>
                            <input type="number" min="0" :max="subtotal" step="any" x-model.number="discount" class="block w-full rounded-lg border-gray-300 text-sm" />
                            <p x-show="err('discount')" class="mt-1 text-xs text-danger-600" x-text="err('discount')"></p>
                        </div>
                        <div class="flex justify-between border-t border-gray-100 pt-3"><span class="font-medium text-gray-600">Total</span><span class="text-lg font-bold text-gray-900" x-text="window.rupiah(total)"></span></div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-gray-500">Jumlah Bayar</label>
                            <input type="number" min="0" step="any" x-model.number="payment" class="block w-full rounded-lg border-gray-300 text-sm" />
                            <p x-show="err('payment_amount')" class="mt-1 text-xs text-danger-600" x-text="err('payment_amount')"></p>
                        </div>
                        <div class="flex justify-between"><span class="text-gray-500">Kembalian</span>
                            <span class="font-semibold" :class="change < 0 ? 'text-danger-600' : 'text-primary-600'" x-text="window.rupiah(change)"></span></div>
                    </div>
                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <a href="{{ route('transactions.show', $id) }}" class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Batal</a>
                        <x-ui.button type="submit" ::disabled="saving || items.length === 0 || change < 0">
                            <span x-text="saving ? 'Menyimpan...' : 'Simpan'"></span>
                        </x-ui.button>
                    </div>
                </x-ui.card>
            </div>
        </form>
    </div>
</x-layouts.app>
