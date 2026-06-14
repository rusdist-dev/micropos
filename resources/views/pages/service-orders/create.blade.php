<x-layouts.app title="Mulai Servis" :breadcrumbs="['Order Servis' => route('service-orders.index'), 'Mulai Servis' => null]">
    <div
        x-data="{
            loadingData: true,
            loadError: null,
            products: [],
            services: [],
            customers: [],
            technicians: [],
            categories: [],

            productSearch: '',
            selectedCategory: '',
            catalogPage: 1,
            catalogPerPage: 8,
            viewMode: 'card',
            cart: [],
            seq: 1,

            selectedCustomer: '',
            selectedTechnician: '',
            dueDate: '',

            showService: false,
            serviceForm: { name: '', price: '', note: '' },

            showCustomer: false,
            savingCustomer: false,
            customerForm: { name: '', phone: '', address: '' },

            discountType: 'amount',
            discountInput: '',

            paymentChoice: 'belum_bayar',   // belum_bayar | dp | lunas
            paidInput: '',

            processing: false,
            showSuccess: false,
            lastOrder: null,

            init() {
                this.viewMode = localStorage.getItem('cashier-view') || 'card';
                this.loadData();
                this.$watch('productSearch', () => this.catalogPage = 1);
                this.$watch('selectedCategory', () => this.catalogPage = 1);
            },

            async loadData() {
                this.loadingData = true; this.loadError = null;
                try {
                    const [prod, cust, svc, cat, tech] = await Promise.all([
                        window.api.get('/api/products?is_active=1&per_page=500'),
                        window.api.get('/api/customers?per_page=500'),
                        window.api.get('/api/services?is_active=1&per_page=500'),
                        window.api.get('/api/categories?all=1'),
                        window.api.get('/api/technicians?is_active=1&per_page=500'),
                    ]);
                    this.products = prod.data.map((p) => ({
                        id: p.id, name: p.name, sku: p.sku, stock: p.stock, min_stock: p.min_stock,
                        category_id: p.category_id, purchase_price: Number(p.purchase_price || 0),
                    }));
                    this.customers = cust.data.map((c) => ({ id: c.id, name: c.name }));
                    this.services = svc.data.map((s) => ({ id: s.id, name: s.name, default_price: s.default_price }));
                    this.categories = cat.data.map((c) => ({ id: c.id, name: c.name }));
                    this.technicians = tech.data.map((t) => ({ id: t.id, name: t.name }));
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

            get filteredProducts() {
                const q = this.productSearch.toLowerCase();
                const cat = this.selectedCategory;
                return this.products.filter((p) =>
                    (! cat || p.category_id == cat)
                    && (! q || p.name.toLowerCase().includes(q) || (p.sku && p.sku.toLowerCase().includes(q)))
                );
            },
            get catalogLastPage() { return Math.max(1, Math.ceil(this.filteredProducts.length / this.catalogPerPage)); },
            get pagedProducts() {
                const page = Math.min(this.catalogPage, this.catalogLastPage);
                const start = (page - 1) * this.catalogPerPage;
                return this.filteredProducts.slice(start, start + this.catalogPerPage);
            },
            goToCatalogPage(p) { this.catalogPage = Math.min(Math.max(1, p), this.catalogLastPage); },

            itemPrice(item) { return item.type === 'service' ? item.price : item.purchase_price; },
            get total() { return this.cart.reduce((s, i) => s + this.itemPrice(i) * i.qty, 0); },
            get discountAmount() {
                const raw = Number(this.discountInput);
                if (! this.discountInput || isNaN(raw) || raw <= 0) return 0;
                const amount = this.discountType === 'percent'
                    ? Math.round(this.total * Math.min(raw, 100) / 100)
                    : raw;
                return Math.min(Math.max(amount, 0), this.total);
            },
            get grandTotal() { return this.total - this.discountAmount; },
            get paidAmount() {
                if (this.paymentChoice === 'lunas') return this.grandTotal;
                if (this.paymentChoice === 'dp') return Math.min(Number(this.paidInput || 0), this.grandTotal);
                return 0;
            },
            get remaining() { return Math.max(0, this.grandTotal - this.paidAmount); },
            resetDiscount() { this.discountType = 'amount'; this.discountInput = ''; },

            addProduct(p) {
                if (p.stock <= 0) return;
                const existing = this.cart.find((i) => i.type === 'product' && i.id === p.id);
                if (existing) {
                    if (existing.qty >= existing.stock) { this.notify('Stok ' + p.name + ' hanya ' + p.stock + ' tersedia.', 'danger'); return; }
                    existing.qty++;
                    return;
                }
                this.cart.push({ key: 'p-' + (this.seq++), type: 'product', id: p.id, name: p.name, purchase_price: p.purchase_price, stock: p.stock, qty: 1 });
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
            clearCart() { this.cart = []; this.selectedCustomer = ''; this.selectedTechnician = ''; this.dueDate = ''; this.resetDiscount(); this.paymentChoice = 'belum_bayar'; this.paidInput = ''; },

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
                    await this.loadCustomers();
                    this.selectedCustomer = res.data.id;
                    this.showCustomer = false;
                    this.notify('Pelanggan baru ditambahkan.', 'success');
                } catch (e) {
                    this.notify(e.status === 422 ? 'Nama pelanggan wajib diisi.' : e.message, 'danger');
                } finally {
                    this.savingCustomer = false;
                }
            },

            async save() {
                if (this.cart.length === 0) { this.notify('Tambahkan minimal satu item.', 'danger'); return; }
                this.processing = true;
                const items = this.cart.map((i) => i.type === 'product'
                    ? { item_type: 'product', product_id: i.id, qty: i.qty }
                    : { item_type: 'service', service_id: i.id ?? null, item_name: i.name, price: i.price, qty: i.qty, note: i.note ?? null });
                try {
                    const res = await window.api.post('/api/service-orders', {
                        customer_id: this.selectedCustomer || null,
                        technician_id: this.selectedTechnician || null,
                        due_date: this.dueDate || null,
                        discount: this.discountAmount,
                        paid_amount: this.paidAmount,
                        note: null,
                        items,
                    });
                    this.lastOrder = res.data;
                    this.showSuccess = true;
                    this.clearCart();
                } catch (e) {
                    this.notify(e.message, 'danger');
                } finally {
                    this.processing = false;
                }
            },
        }"
        class="flex flex-col gap-4 lg:h-[calc(100vh-8rem)]"
    >
        <template x-if="loadError">
            <x-ui.alert variant="danger" title="Gagal memuat data servis">
                <span x-text="loadError"></span>
            </x-ui.alert>
        </template>

        <div class="grid grid-cols-1 gap-4 lg:min-h-0 lg:flex-1 lg:grid-cols-5">
            <div class="relative h-[60vh] rounded-xl border border-gray-200 bg-white p-4 lg:h-auto lg:col-span-3">
                <div x-show="loadingData" class="absolute inset-0 z-10 flex items-center justify-center bg-white/70">
                    <x-ui.loading-spinner size="lg" label="Memuat produk..." />
                </div>
                <x-service.product-grid />
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white lg:col-span-2">
                <x-service.order-panel />
            </div>
        </div>

        {{-- Modals --}}
        <x-service.service-input />
        <x-service.customer-input />

        {{-- Sukses --}}
        <x-ui.modal title="Order Servis Dibuat" size="sm" show="showSuccess">
            <div class="text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-primary-50 text-primary-600"><x-heroicon-o-check-circle class="h-9 w-9" /></div>
                <p class="mt-4 text-sm text-gray-600">Servis dimulai (status Proses).</p>
                <p class="mt-1 text-sm text-gray-500">No: <span class="font-medium text-gray-800" x-text="lastOrder?.invoice_number"></span></p>
                <p class="mt-1 text-sm text-gray-500">Sisa Tagihan: <span class="font-semibold text-gray-800" x-text="rupiah(lastOrder?.remaining)"></span></p>
            </div>
            <x-slot:footer>
                <x-ui.button variant="outline" type="button" @click="showSuccess = false">Buat Lagi</x-ui.button>
                <a :href="'{{ url('service-orders') }}/' + lastOrder?.id">
                    <x-ui.button type="button" icon="arrow-right">Lihat Detail</x-ui.button>
                </a>
            </x-slot:footer>
        </x-ui.modal>
    </div>
</x-layouts.app>
