<x-layouts.app title="Kasir">
    <div
        x-data="{
            loadingData: true,
            loadError: null,
            products: [],
            services: [],
            customers: [],
            priceTypes: [],
            categories: [],
            quickAmounts: [50000, 100000, 200000],

            productSearch: '',
            selectedCategory: '',
            catalogPage: 1,
            catalogPerPage: 8,
            viewMode: 'card',
            cart: [],
            selectedCustomer: '',
            priceType: '',
            seq: 1,

            showService: false,
            serviceForm: { name: '', price: '', note: '' },

            showCustomer: false,
            savingCustomer: false,
            customerForm: { name: '', phone: '', address: '' },

            showPayment: false,
            paymentAmount: '',
            processing: false,

            discountType: 'amount',  // 'amount' (Rp) | 'percent' (%)
            discountInput: '',       // input bebas oleh kasir

            showSuccess: false,
            lastChange: 0,
            lastTransaction: null,

            showDrafts: false,
            drafts: [],

            init() {
                this.viewMode = localStorage.getItem('cashier-view') || 'card';
                this.loadDrafts();
                this.loadData();
                this.$watch('priceType', () => { this.reconcileCart(); this.catalogPage = 1; });
                // Reset ke halaman 1 setiap kali filter katalog berubah.
                this.$watch('productSearch', () => this.catalogPage = 1);
                this.$watch('selectedCategory', () => this.catalogPage = 1);
            },

            async loadData() {
                this.loadingData = true;
                this.loadError = null;
                try {
                    const [prod, pt, cust, svc, cat] = await Promise.all([
                        window.api.get('/api/products?is_active=1&per_page=500'),
                        window.api.get('/api/price-types?all=1&is_active=1'),
                        window.api.get('/api/customers?per_page=500'),
                        window.api.get('/api/services?is_active=1&per_page=500'),
                        window.api.get('/api/categories?all=1'),
                    ]);
                    this.products = prod.data.map((p) => ({
                        id: p.id, name: p.name, sku: p.sku, stock: p.stock, min_stock: p.min_stock,
                        category_id: p.category_id,
                        prices: Object.fromEntries((p.prices || []).map((r) => [r.price_type, r.price])),
                    }));
                    this.priceTypes = pt.data.map((t) => ({ code: t.code, name: t.name }));
                    this.customers = cust.data.map((c) => ({ id: c.id, name: c.name }));
                    this.services = svc.data.map((s) => ({ id: s.id, name: s.name, default_price: s.default_price }));
                    this.categories = cat.data.map((c) => ({ id: c.id, name: c.name }));
                    if (! this.priceType && this.priceTypes.length) this.priceType = this.priceTypes[0].code;
                } catch (e) {
                    this.loadError = e.message;
                } finally {
                    this.loadingData = false;
                }
            },
            async loadCustomers() {
                const res = await window.api.get('/api/customers?per_page=500');
                this.customers = res.data.map((c) => ({ id: c.id, name: c.name }));
            },

            setView(mode) { this.viewMode = mode; localStorage.setItem('cashier-view', mode); },
            notify(message, type = 'info') { this.$store.toasts.push(message, type); },

            rupiah(v) { return 'Rp ' + Number(v || 0).toLocaleString('id-ID'); },
            priceTypeName(code) { const t = this.priceTypes.find((t) => t.code === code); return t ? t.name : code; },
            hasPrice(p, code) { return p.prices && p.prices[code] !== undefined && p.prices[code] !== null; },

            get filteredProducts() {
                const q = this.productSearch.toLowerCase();
                const cat = this.selectedCategory;
                return this.products.filter((p) =>
                    this.hasPrice(p, this.priceType)
                    && (! cat || p.category_id == cat)
                    && (! q || p.name.toLowerCase().includes(q) || (p.sku && p.sku.toLowerCase().includes(q)))
                );
            },
            // Pagination katalog (client-side) atas hasil filter.
            get catalogLastPage() { return Math.max(1, Math.ceil(this.filteredProducts.length / this.catalogPerPage)); },
            get pagedProducts() {
                const page = Math.min(this.catalogPage, this.catalogLastPage);
                const start = (page - 1) * this.catalogPerPage;
                return this.filteredProducts.slice(start, start + this.catalogPerPage);
            },
            goToCatalogPage(p) { this.catalogPage = Math.min(Math.max(1, p), this.catalogLastPage); },
            get total() { return this.cart.reduce((s, i) => s + this.itemPrice(i) * i.qty, 0); }, // subtotal (sebelum diskon)
            // Diskon dihitung dari input bebas; di-clamp agar 0 <= diskon <= subtotal.
            get discountAmount() {
                const raw = Number(this.discountInput);
                if (! this.discountInput || isNaN(raw) || raw <= 0) return 0;
                const amount = this.discountType === 'percent'
                    ? Math.round(this.total * Math.min(raw, 100) / 100)
                    : raw;
                return Math.min(Math.max(amount, 0), this.total);
            },
            get grandTotal() { return this.total - this.discountAmount; },
            get change() { return this.paymentAmount === '' ? 0 : Number(this.paymentAmount) - this.grandTotal; },
            itemPrice(item) { return item.type === 'service' ? item.price : (item.prices[this.priceType] ?? 0); },
            resetDiscount() { this.discountType = 'amount'; this.discountInput = ''; },

            addProduct(p) {
                if (p.stock <= 0) return;
                if (! this.hasPrice(p, this.priceType)) {
                    this.notify(p.name + ' tidak punya harga ' + this.priceTypeName(this.priceType) + '.', 'danger');
                    return;
                }
                const existing = this.cart.find((i) => i.type === 'product' && i.id === p.id);
                if (existing) {
                    if (existing.qty >= existing.stock) { this.notify('Stok ' + p.name + ' hanya ' + p.stock + ' tersedia.', 'danger'); return; }
                    existing.qty++;
                    return;
                }
                this.cart.push({ key: 'p-' + (this.seq++), type: 'product', id: p.id, name: p.name, prices: p.prices, stock: p.stock, qty: 1 });
            },
            incQty(idx) {
                const item = this.cart[idx];
                if (item.type === 'product' && item.qty >= item.stock) { this.notify('Stok ' + item.name + ' hanya ' + item.stock + ' tersedia.', 'danger'); return; }
                item.qty++;
            },
            decQty(idx) { if (this.cart[idx].qty > 1) this.cart[idx].qty--; else this.removeItem(idx); },
            clampQty(idx) {
                const item = this.cart[idx];
                let q = Math.floor(Number(item.qty));
                if (isNaN(q) || q < 1) q = 1;
                if (item.type === 'product' && q > item.stock) { q = item.stock; this.notify('Stok ' + item.name + ' hanya ' + item.stock + ' tersedia.', 'danger'); }
                item.qty = q;
            },
            removeItem(idx) { this.cart.splice(idx, 1); },
            clearCart() { this.cart = []; this.selectedCustomer = ''; this.resetDiscount(); },
            reconcileCart() {
                const removed = this.cart.filter((i) => i.type === 'product' && ! this.hasPrice(i, this.priceType));
                if (removed.length === 0) return;
                this.cart = this.cart.filter((i) => i.type !== 'product' || this.hasPrice(i, this.priceType));
                this.notify('Dihapus (tanpa harga ' + this.priceTypeName(this.priceType) + '): ' + removed.map((i) => i.name).join(', '), 'danger');
            },

            openServiceModal() { this.serviceForm = { name: '', price: '', note: '' }; this.showService = true; },
            addService() {
                if (! this.serviceForm.name || this.serviceForm.price === '') return;
                this.cart.push({ key: 's-' + (this.seq++), type: 'service', id: null, name: this.serviceForm.name, price: Number(this.serviceForm.price), qty: 1, note: this.serviceForm.note });
                this.showService = false;
            },

            openCustomerModal() { this.customerForm = { name: '', phone: '', address: '' }; this.showCustomer = true; },
            async addCustomer() {
                if (! this.customerForm.name) return;
                this.savingCustomer = true;
                try {
                    const res = await window.api.post('/api/customers', this.customerForm);
                    await this.loadCustomers();        // refetch dropdown
                    this.selectedCustomer = res.data.id; // auto-pilih
                    this.showCustomer = false;
                    this.notify('Pelanggan baru ditambahkan.', 'success');
                } catch (e) {
                    this.notify(e.status === 422 ? 'Nama pelanggan wajib diisi.' : e.message, 'danger');
                } finally {
                    this.savingCustomer = false;
                }
            },

            openPaymentModal() {
                if (this.cart.length === 0) return;
                this.paymentAmount = '';
                this.showPayment = true;
                this.$nextTick(() => this.$refs.payInput && this.$refs.payInput.focus());
            },
            async checkout() {
                if (this.paymentAmount === '' || this.change < 0) return;
                this.processing = true;
                const items = this.cart.map((i) => i.type === 'product'
                    ? { item_type: 'product', product_id: i.id, price_type: this.priceType, qty: i.qty }
                    : { item_type: 'service', service_id: i.id ?? null, item_name: i.name, price: i.price, qty: i.qty, note: i.note ?? null });
                try {
                    const res = await window.api.post('/api/transactions', {
                        customer_id: this.selectedCustomer || null,
                        discount: this.discountAmount,
                        payment_amount: Number(this.paymentAmount),
                        items,
                    });
                    this.lastTransaction = res.data;
                    this.lastChange = res.data.change_amount;
                    this.showPayment = false;
                    this.showSuccess = true;
                    this.cart = [];
                    this.selectedCustomer = '';
                    this.paymentAmount = '';
                    this.resetDiscount();
                    this.loadData(); // refresh stok produk
                } catch (e) {
                    this.notify(e.message, 'danger');
                } finally {
                    this.processing = false;
                }
            },

            loadDrafts() { try { this.drafts = JSON.parse(localStorage.getItem('cashier-drafts') || '[]'); } catch (e) { this.drafts = []; } },
            persistDrafts() { localStorage.setItem('cashier-drafts', JSON.stringify(this.drafts)); },
            customerName(id) { if (! id) return 'Pelanggan Umum'; const c = this.customers.find((c) => c.id == id); return c ? c.name : 'Pelanggan Umum'; },
            saveDraft() {
                if (this.cart.length === 0) return;
                this.drafts.unshift({
                    id: Date.now(), label: this.customerName(this.selectedCustomer),
                    cart: JSON.parse(JSON.stringify(this.cart)), selectedCustomer: this.selectedCustomer, priceType: this.priceType,
                    discountType: this.discountType, discountInput: this.discountInput,
                    itemCount: this.cart.length, total: this.total,
                    savedAt: new Date().toLocaleString('id-ID', { dateStyle: 'short', timeStyle: 'short' }),
                });
                this.persistDrafts();
                this.cart = []; this.selectedCustomer = ''; this.resetDiscount(); this.notify('Transaksi disimpan sebagai draft.', 'success');
            },
            openDraft(draft) {
                if (this.cart.length > 0) this.saveDraft();
                this.cart = JSON.parse(JSON.stringify(draft.cart));
                this.selectedCustomer = draft.selectedCustomer;
                this.priceType = draft.priceType;
                this.discountType = draft.discountType || 'amount';
                this.discountInput = draft.discountInput || '';
                this.deleteDraft(draft.id);
                this.showDrafts = false;
            },
            deleteDraft(id) { this.drafts = this.drafts.filter((d) => d.id !== id); this.persistDrafts(); },
        }"
        class="flex flex-col gap-4 lg:h-[calc(100vh-8rem)]"
    >
        {{-- Toolbar atas --}}
        <div class="flex items-center justify-end">
            <button type="button" @click="showDrafts = true"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                <x-heroicon-o-inbox-stack class="h-5 w-5 text-gray-400" />
                Draft Tersimpan
                <span x-show="drafts.length" x-cloak class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-primary-600 px-1.5 text-xs font-semibold text-white" x-text="drafts.length"></span>
            </button>
        </div>

        {{-- Error muat data --}}
        <template x-if="loadError">
            <x-ui.alert variant="danger" title="Gagal memuat data kasir">
                <span x-text="loadError"></span>
            </x-ui.alert>
        </template>

        {{-- Konten 2 kolom (stack di mobile, berdampingan di desktop) --}}
        <div class="grid grid-cols-1 gap-4 lg:min-h-0 lg:flex-1 lg:grid-cols-5">
            <div class="relative h-[60vh] rounded-xl border border-gray-200 bg-white p-4 lg:h-auto lg:col-span-3">
                <div x-show="loadingData" class="absolute inset-0 z-10 flex items-center justify-center bg-white/70">
                    <x-ui.loading-spinner size="lg" label="Memuat produk..." />
                </div>
                <x-cashier.product-grid />
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white lg:col-span-2">
                <x-cashier.cart-panel />
            </div>
        </div>

        {{-- Toast lokal ditangani store global. --}}

        {{-- Modals --}}
        <x-cashier.service-input />
        <x-cashier.customer-input />
        <x-cashier.payment-modal />

        {{-- Modal daftar draft --}}
        <x-ui.modal title="Draft Transaksi" size="lg" show="showDrafts">
            <div x-show="drafts.length === 0" class="py-8 text-center text-sm text-gray-400">Belum ada draft tersimpan.</div>
            <div x-show="drafts.length > 0" class="max-h-96 space-y-2 overflow-y-auto scrollbar-thin">
                <template x-for="d in drafts" :key="d.id">
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-gray-200 p-3">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-gray-800" x-text="d.label"></p>
                            <p class="text-xs text-gray-400"><span x-text="d.itemCount"></span> item &middot; <span x-text="rupiah(d.total)"></span> &middot; <span x-text="d.savedAt"></span></p>
                        </div>
                        <div class="flex flex-shrink-0 items-center gap-1">
                            <x-ui.button size="sm" type="button" icon="arrow-up-tray" @click="openDraft(d)">Lanjutkan</x-ui.button>
                            <button type="button" @click="deleteDraft(d.id)" class="rounded-md p-2 text-gray-400 transition hover:bg-danger-50 hover:text-danger-600" title="Hapus draft"><x-heroicon-o-trash class="h-4 w-4" /></button>
                        </div>
                    </div>
                </template>
            </div>
            <x-slot:footer><x-ui.button variant="outline" type="button" @click="showDrafts = false">Tutup</x-ui.button></x-slot:footer>
        </x-ui.modal>

        {{-- Sukses --}}
        <x-ui.modal title="Transaksi Berhasil" size="sm" show="showSuccess">
            <div class="text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-primary-50 text-primary-600"><x-heroicon-o-check-circle class="h-9 w-9" /></div>
                <p class="mt-4 text-sm text-gray-600">Pembayaran berhasil diproses.</p>
                <p class="mt-1 text-sm text-gray-500">No: <span class="font-medium text-gray-800" x-text="lastTransaction?.invoice_number"></span></p>
                <p class="mt-1 text-sm text-gray-500">Kembalian: <span class="font-semibold text-gray-800" x-text="rupiah(lastChange)"></span></p>
            </div>
            <x-slot:footer>
                <x-ui.button variant="outline" type="button" @click="showSuccess = false">Tutup</x-ui.button>
                <x-ui.button variant="outline" type="button" icon="printer" @click="$store.receipt.print(lastTransaction, 'thermal')">Struk 80mm</x-ui.button>
                <x-ui.button type="button" icon="document-text" @click="$store.receipt.print(lastTransaction, 'a4')">Cetak A4</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    </div>
</x-layouts.app>
