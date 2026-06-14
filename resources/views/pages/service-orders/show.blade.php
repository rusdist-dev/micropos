<x-layouts.app title="Detail Servis" :breadcrumbs="['Order Servis' => route('service-orders.index'), 'Detail' => null]">
    <div
        x-data="{
            id: {{ (int) $id }},
            loading: true, error: null, saving: false,
            order: null,

            // Master untuk pengeditan
            products: [], services: [], technicians: [],
            productStock: {},     // product_id => stok terkini
            baseConsumption: {},  // product_id => qty tersimpan saat ini (untuk hitung batas stok)

            // State editable (saat Proses)
            rows: [],
            technicianId: '', dueDate: '',
            discountType: 'amount', discountInput: '',
            seq: 1,

            showAddProduct: false, productSearch: '',
            showAddService: false, serviceForm: { name: '', price: '', note: '' },
            showComplete: false, paymentInput: '', completing: false,
            showCancel: false, cancelMode: 'create', cancelNote: '', cancelFee: '', canceling: false,

            get isProcess() { return this.order?.service_status === 'process'; },
            get isBatal() { return this.order?.service_status === 'batal'; },
            get cancelRefund() { return Math.max(0, Number(this.order?.paid_amount || 0) - Number(this.cancelFee || 0)); },
            rupiah(v) { return 'Rp ' + Number(v || 0).toLocaleString('id-ID'); },
            fmtDate(iso) { return iso ? new Date(iso).toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'short' }) : '—'; },
            fmtDay(d) { return d ? new Date(d).toLocaleDateString('id-ID', { dateStyle: 'long' }) : '—'; },
            notify(m, t = 'info') { this.$store.toasts.push(m, t); },

            statusClass(s) {
                return s === 'process' ? 'bg-warning-50 text-warning-700 ring-warning-600/20'
                    : s === 'selesai' ? 'bg-primary-50 text-primary-700 ring-primary-600/20'
                    : 'bg-danger-50 text-danger-700 ring-danger-600/20';
            },

            async load() {
                this.loading = true; this.error = null;
                try {
                    const res = await window.api.get('/api/service-orders/' + this.id);
                    this.order = res.data;
                    this.technicianId = res.data.technician_id ?? '';
                    this.dueDate = res.data.due_date ?? '';
                    this.discountType = 'amount';
                    this.discountInput = res.data.discount > 0 ? res.data.discount : '';
                    this.baseConsumption = {};
                    this.rows = res.data.items.map((it) => {
                        if (it.item_type === 'product' && it.product_id) {
                            this.baseConsumption[it.product_id] = (this.baseConsumption[it.product_id] || 0) + it.qty;
                        }
                        return {
                            key: (it.item_type === 'product' ? 'p-' : 's-') + (this.seq++),
                            type: it.item_type,
                            product_id: it.product_id,
                            service_id: it.service_id,
                            name: it.item_name,
                            price: it.price_snapshot,
                            qty: it.qty,
                            note: it.note ?? '',
                        };
                    });
                    if (this.isProcess) await this.loadMasters();
                } catch (e) { this.error = e.message; } finally { this.loading = false; }
            },
            async loadMasters() {
                try {
                    const [prod, svc, tech] = await Promise.all([
                        window.api.get('/api/products?is_active=1&per_page=500'),
                        window.api.get('/api/services?is_active=1&per_page=500'),
                        window.api.get('/api/technicians?is_active=1&per_page=500'),
                    ]);
                    this.products = prod.data.map((p) => ({ id: p.id, name: p.name, sku: p.sku, stock: p.stock, purchase_price: Number(p.purchase_price || 0) }));
                    this.productStock = Object.fromEntries(this.products.map((p) => [p.id, p.stock]));
                    this.services = svc.data.map((s) => ({ id: s.id, name: s.name, default_price: s.default_price }));
                    this.technicians = tech.data.map((t) => ({ id: t.id, name: t.name }));
                } catch (e) { this.notify(e.message, 'danger'); }
            },

            // Batas qty produk = stok terkini + qty yang sudah tersimpan di order ini.
            maxQty(row) {
                if (row.type !== 'product' || ! row.product_id) return 999999;
                return (this.productStock[row.product_id] || 0) + (this.baseConsumption[row.product_id] || 0);
            },
            get filteredProducts() {
                const q = this.productSearch.toLowerCase();
                return this.products.filter((p) => ! q || p.name.toLowerCase().includes(q) || (p.sku && p.sku.toLowerCase().includes(q)));
            },

            get total() { return this.rows.reduce((s, r) => s + Number(r.price) * Number(r.qty), 0); },
            get discountAmount() {
                const raw = Number(this.discountInput);
                if (! this.discountInput || isNaN(raw) || raw <= 0) return 0;
                const amount = this.discountType === 'percent' ? Math.round(this.total * Math.min(raw, 100) / 100) : raw;
                return Math.min(Math.max(amount, 0), this.total);
            },
            get grandTotal() { return this.total - this.discountAmount; },

            addProduct(p) {
                const existing = this.rows.find((r) => r.type === 'product' && r.product_id === p.id);
                if (existing) {
                    if (existing.qty >= this.maxQty(existing)) { this.notify('Stok ' + p.name + ' tidak mencukupi.', 'danger'); return; }
                    existing.qty++;
                } else {
                    this.rows.push({ key: 'p-' + (this.seq++), type: 'product', product_id: p.id, service_id: null, name: p.name, price: p.purchase_price, qty: 1, note: '' });
                }
                this.showAddProduct = false; this.productSearch = '';
            },
            incQty(idx) {
                const r = this.rows[idx];
                if (r.type === 'product' && r.qty >= this.maxQty(r)) { this.notify('Stok ' + r.name + ' tidak mencukupi.', 'danger'); return; }
                r.qty++;
            },
            decQty(idx) { if (this.rows[idx].qty > 1) this.rows[idx].qty--; else this.removeRow(idx); },
            clampQty(idx) {
                const r = this.rows[idx];
                let q = Math.floor(Number(r.qty));
                if (isNaN(q) || q < 1) q = 1;
                if (r.type === 'product' && q > this.maxQty(r)) { q = this.maxQty(r); this.notify('Stok ' + r.name + ' tidak mencukupi.', 'danger'); }
                r.qty = q;
            },
            removeRow(idx) { this.rows.splice(idx, 1); },

            openAddService() { this.serviceForm = { name: '', price: '', note: '' }; this.showAddService = true; },
            addService() {
                if (! this.serviceForm.name || this.serviceForm.price === '') return;
                this.rows.push({ key: 's-' + (this.seq++), type: 'service', product_id: null, service_id: null, name: this.serviceForm.name, price: Number(this.serviceForm.price), qty: 1, note: this.serviceForm.note });
                this.showAddService = false;
            },

            payload() {
                return {
                    technician_id: this.technicianId || null,
                    due_date: this.dueDate || null,
                    discount: this.discountAmount,
                    items: this.rows.map((r) => r.type === 'product'
                        ? { item_type: 'product', product_id: r.product_id, qty: r.qty, note: r.note || null }
                        : { item_type: 'service', service_id: r.service_id ?? null, item_name: r.name, price: r.price, qty: r.qty, note: r.note || null }),
                };
            },
            async save() {
                if (this.rows.length === 0) { this.notify('Order harus punya minimal satu item.', 'danger'); return; }
                this.saving = true;
                try {
                    await window.api.put('/api/service-orders/' + this.id, this.payload());
                    this.notify('Perubahan tersimpan.', 'success');
                    await this.load();
                } catch (e) { this.notify(e.message, 'danger'); } finally { this.saving = false; }
            },

            openComplete() { this.paymentInput = this.order.remaining > 0 ? this.order.remaining : ''; this.showComplete = true; },
            async complete() {
                this.completing = true;
                try {
                    // Simpan perubahan item dulu agar total final akurat.
                    await window.api.put('/api/service-orders/' + this.id, this.payload());
                    await window.api.post('/api/service-orders/' + this.id + '/complete', { payment: Number(this.paymentInput || 0) });
                    this.showComplete = false;
                    this.notify('Servis selesai.', 'success');
                    await this.load();
                } catch (e) { this.notify(e.message, 'danger'); } finally { this.completing = false; }
            },

            openCancel(mode) {
                this.cancelMode = mode;
                if (mode === 'adjust') {
                    this.cancelNote = this.order?.cancel_note ?? '';
                    this.cancelFee = this.order?.cancellation_fee ?? 0;
                } else {
                    this.cancelNote = '';
                    this.cancelFee = this.order?.paid_amount ?? 0; // default: tahan seluruh DP
                }
                this.showCancel = true;
            },
            async cancel() {
                if (! this.cancelNote.trim()) { this.notify('Keterangan pembatalan wajib diisi.', 'danger'); return; }
                this.canceling = true;
                const url = this.cancelMode === 'adjust'
                    ? '/api/service-orders/' + this.id + '/cancellation-fee'
                    : '/api/service-orders/' + this.id + '/cancel';
                try {
                    await window.api.post(url, { cancel_note: this.cancelNote, cancellation_fee: Number(this.cancelFee || 0) });
                    this.showCancel = false;
                    this.notify(this.cancelMode === 'adjust' ? 'Biaya pembatalan diperbarui.' : 'Servis dibatalkan & stok bahan dikembalikan.', 'success');
                    await this.load();
                } catch (e) { this.notify(e.message, 'danger'); } finally { this.canceling = false; }
            },
        }"
        x-init="load()"
    >
        <div x-show="loading" class="py-10"><x-ui.loading-spinner size="lg" label="Memuat..." /></div>
        <template x-if="error"><x-ui.alert variant="danger" title="Gagal memuat"><span x-text="error"></span></x-ui.alert></template>

        <div x-show="!loading && order" x-cloak class="space-y-4">
            {{-- Header --}}
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-lg font-semibold text-gray-900" x-text="order?.invoice_number"></span>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset" :class="statusClass(order?.service_status)" x-text="order?.service_status_label"></span>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset bg-gray-100 text-gray-600 ring-gray-500/20" x-text="order?.payment_status_label"></span>
                    </div>
                    <p class="mt-0.5 text-sm text-gray-500">Dibuat <span x-text="fmtDate(order?.created_at)"></span> oleh <span x-text="order?.operator_name || '—'"></span></p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <x-ui.dropdown align="right" width="48">
                        <x-slot:trigger>
                            <x-ui.button variant="outline" type="button" icon="printer">
                                Cetak
                                <x-heroicon-o-chevron-down class="h-4 w-4" />
                            </x-ui.button>
                        </x-slot:trigger>
                        <x-slot:content>
                            <button type="button" @click="$store.receipt.printService(order, 'thermal')" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-gray-700 transition hover:bg-gray-50">
                                <x-heroicon-o-printer class="h-4 w-4 text-gray-400" />
                                Struk 80mm
                            </button>
                            <button type="button" @click="$store.receipt.printService(order, 'a4')" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-gray-700 transition hover:bg-gray-50">
                                <x-heroicon-o-document-text class="h-4 w-4 text-gray-400" />
                                Cetak A4
                            </button>
                        </x-slot:content>
                    </x-ui.dropdown>
                    <div class="flex flex-wrap items-center gap-2" x-show="isProcess">
                        <x-ui.button variant="outline" type="button" icon="bookmark" ::disabled="saving" @click="save()"><span x-text="saving ? '...' : 'Simpan'"></span></x-ui.button>
                        @can('service-orders.cancel')
                            <x-ui.button variant="danger" type="button" icon="x-mark" @click="openCancel('create')">Batal Servis</x-ui.button>
                        @endcan
                        @can('service-orders.complete')
                            <x-ui.button type="button" icon="check" @click="openComplete()">Servis Selesai</x-ui.button>
                        @endcan
                    </div>
                </div>
            </div>

            {{-- Info ringkas --}}
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-xl border border-gray-200 bg-white p-3">
                    <p class="text-xs text-gray-400">Pelanggan</p>
                    <p class="mt-0.5 text-sm font-medium text-gray-800" x-text="order?.customer_name || 'Pelanggan Umum'"></p>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-3">
                    <p class="text-xs text-gray-400">Teknisi</p>
                    <template x-if="!isProcess"><p class="mt-0.5 text-sm font-medium text-gray-800" x-text="order?.technician_name || '—'"></p></template>
                    <template x-if="isProcess">
                        <select @change="technicianId = $event.target.value" class="mt-0.5 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                            <option value="" :selected="! technicianId">— Pilih —</option>
                            <template x-for="t in technicians" :key="t.id"><option :value="t.id" :selected="String(t.id) === String(technicianId)" x-text="t.name"></option></template>
                        </select>
                    </template>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-3">
                    <p class="text-xs text-gray-400">Tenggang Waktu</p>
                    <template x-if="!isProcess"><p class="mt-0.5 text-sm font-medium text-gray-800" x-text="fmtDay(order?.due_date)"></p></template>
                    <template x-if="isProcess">
                        <input type="date" x-model="dueDate" class="mt-0.5 block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                    </template>
                </div>
                <div class="rounded-xl border border-gray-200 bg-white p-3">
                    <p class="text-xs text-gray-400">Tanggal Selesai</p>
                    <p class="mt-0.5 text-sm font-medium text-gray-800" x-text="fmtDate(order?.completed_at)"></p>
                </div>
            </div>

            {{-- Keterangan & biaya pembatalan --}}
            <template x-if="isBatal">
                <div class="rounded-xl border border-danger-200 bg-danger-50 p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="flex items-center gap-1.5 text-sm font-semibold text-danger-800">
                                <x-heroicon-o-x-circle class="h-5 w-5" /> Servis Dibatalkan
                            </p>
                            <p class="mt-1 text-sm text-danger-700" x-text="order?.cancel_note || '—'"></p>
                            <p class="mt-0.5 text-xs text-danger-600" x-show="order?.canceled_at" x-text="'Dibatalkan: ' + fmtDate(order?.canceled_at)"></p>
                        </div>
                        @can('service-orders.cancel')
                            <x-ui.button variant="outline" size="sm" type="button" icon="pencil-square" @click="openCancel('adjust')">Ubah Biaya</x-ui.button>
                        @endcan
                    </div>
                    <div class="mt-3 grid grid-cols-3 gap-2 border-t border-danger-200 pt-3 text-sm">
                        <div><p class="text-xs text-danger-600">DP Dibayar</p><p class="font-medium text-danger-800" x-text="rupiah(order?.paid_amount)"></p></div>
                        <div><p class="text-xs text-danger-600">Biaya Pembatalan</p><p class="font-semibold text-danger-800" x-text="rupiah(order?.cancellation_fee)"></p></div>
                        <div><p class="text-xs text-danger-600">Dikembalikan</p><p class="font-medium text-danger-800" x-text="rupiah(order?.refund_amount)"></p></div>
                    </div>
                </div>
            </template>

            {{-- Item --}}
            <div class="rounded-xl border border-gray-200 bg-white">
                <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
                    <h3 class="text-sm font-semibold text-gray-800">Item Servis</h3>
                    <div class="flex items-center gap-2" x-show="isProcess">
                        <x-ui.button variant="outline" size="sm" type="button" icon="cube" @click="productSearch = ''; showAddProduct = true">Tambah Bahan</x-ui.button>
                        <x-ui.button variant="outline" size="sm" type="button" icon="wrench-screwdriver" @click="openAddService()">Tambah Jasa</x-ui.button>
                    </div>
                </div>

                <x-ui.table>
                    <x-slot:head>
                        <x-ui.th>Item</x-ui.th>
                        <x-ui.th align="right">Harga</x-ui.th>
                        <x-ui.th align="center">Qty</x-ui.th>
                        <x-ui.th align="right">Subtotal</x-ui.th>
                        <x-ui.th align="right" x-show="isProcess"></x-ui.th>
                    </x-slot:head>
                    <template x-for="(r, idx) in rows" :key="r.key">
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-1">
                                <p class="text-sm font-medium text-gray-800" x-text="r.name"></p>
                                <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-medium"
                                    :class="r.type === 'service' ? 'bg-warning-50 text-warning-700' : 'bg-gray-100 text-gray-500'"
                                    x-text="r.type === 'service' ? 'Jasa' : 'Bahan'"></span>
                            </td>
                            <td class="px-4 py-1 text-right text-sm text-gray-600" x-text="rupiah(r.price)"></td>
                            <td class="px-4 py-1 text-center">
                                <template x-if="isProcess && r.type === 'product'">
                                    <div class="inline-flex items-center rounded-lg border border-gray-200">
                                        <button type="button" @click="decQty(idx)" class="px-2 py-1 text-gray-500 transition hover:bg-gray-50"><x-heroicon-o-minus class="h-3.5 w-3.5" /></button>
                                        <input type="number" min="1" x-model.number="r.qty" @input="clampQty(idx)" class="w-10 border-0 p-0 text-center text-sm focus:ring-0" />
                                        <button type="button" @click="incQty(idx)" class="px-2 py-1 text-gray-500 transition hover:bg-gray-50"><x-heroicon-o-plus class="h-3.5 w-3.5" /></button>
                                    </div>
                                </template>
                                <template x-if="!isProcess || r.type === 'service'"><span class="text-sm text-gray-600" x-text="r.qty"></span></template>
                            </td>
                            <td class="px-4 py-1 text-right text-sm font-semibold text-gray-800" x-text="rupiah(r.price * r.qty)"></td>
                            <td class="px-4 py-1 text-right" x-show="isProcess">
                                <button type="button" @click="removeRow(idx)" class="rounded p-1 text-gray-300 transition hover:bg-danger-50 hover:text-danger-600"><x-heroicon-o-trash class="h-4 w-4" /></button>
                            </td>
                        </tr>
                    </template>
                </x-ui.table>

                {{-- Ringkasan --}}
                <div class="border-t border-gray-100 p-4">
                    <div class="ml-auto max-w-xs space-y-1">
                        <div class="flex items-center justify-between text-sm text-gray-500"><span>Subtotal</span><span x-text="rupiah(total)"></span></div>
                        <template x-if="isProcess">
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="text-gray-500">Diskon</span>
                                    <div class="inline-flex overflow-hidden rounded-md border border-gray-200 text-xs font-medium">
                                        <button type="button" @click="discountType = 'amount'" class="px-2 py-0.5" :class="discountType === 'amount' ? 'bg-primary-600 text-white' : 'text-gray-500'">Rp</button>
                                        <button type="button" @click="discountType = 'percent'" class="px-2 py-0.5" :class="discountType === 'percent' ? 'bg-primary-600 text-white' : 'text-gray-500'">%</button>
                                    </div>
                                    <input type="number" min="0" x-model="discountInput" placeholder="0" class="w-20 rounded-md border-gray-300 py-0.5 text-right text-xs shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                                </div>
                                <span class="text-danger-600" x-text="'− ' + rupiah(discountAmount)"></span>
                            </div>
                        </template>
                        <template x-if="!isProcess && order?.discount > 0">
                            <div class="flex items-center justify-between text-sm text-danger-600"><span>Diskon</span><span x-text="'− ' + rupiah(order?.discount)"></span></div>
                        </template>
                        <div class="flex items-center justify-between border-t border-gray-100 pt-1 text-base"><span class="font-medium text-gray-600">Total</span><span class="font-bold text-gray-900" x-text="rupiah(isProcess ? grandTotal : order?.total)"></span></div>
                        <div class="flex items-center justify-between text-sm text-gray-500"><span>Terbayar</span><span x-text="rupiah(order?.paid_amount)"></span></div>
                        <div class="flex items-center justify-between text-sm"><span class="text-gray-500">Sisa</span><span class="font-semibold text-danger-600" x-text="rupiah(order?.remaining)"></span></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Modal tambah bahan --}}
        <x-ui.modal title="Tambah Bahan" size="md" show="showAddProduct">
            <div class="mb-3"><x-ui.search-input placeholder="Cari produk (nama / SKU)..." model="productSearch" /></div>
            <div class="max-h-80 space-y-2 overflow-y-auto scrollbar-thin">
                <template x-for="p in filteredProducts" :key="p.id">
                    <button type="button" @click="addProduct(p)" :disabled="p.stock <= 0"
                        class="flex w-full items-center justify-between gap-3 rounded-lg border border-gray-200 p-2.5 text-left transition hover:border-primary-300 hover:bg-primary-50/40 disabled:cursor-not-allowed disabled:opacity-50">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-gray-800" x-text="p.name"></p>
                            <p class="text-[11px] text-gray-400">Stok: <span x-text="p.stock"></span></p>
                        </div>
                        <span class="text-sm font-semibold text-primary-600" x-text="rupiah(p.purchase_price)"></span>
                    </button>
                </template>
                <p x-show="filteredProducts.length === 0" class="py-6 text-center text-sm text-gray-400">Produk tidak ditemukan.</p>
            </div>
            <x-slot:footer><x-ui.button variant="outline" type="button" @click="showAddProduct = false">Tutup</x-ui.button></x-slot:footer>
        </x-ui.modal>

        {{-- Modal tambah jasa --}}
        <x-ui.modal title="Tambah Jasa" size="md" show="showAddService">
            <div class="space-y-4">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Nama Jasa <span class="text-danger-500">*</span></label>
                    <input type="text" x-model="serviceForm.name" placeholder="cth. Servis Mainboard" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        <template x-for="s in services" :key="s.id">
                            <button type="button" @click="serviceForm.name = s.name; serviceForm.price = s.default_price" class="rounded-full bg-gray-100 px-2.5 py-1 text-xs text-gray-600 transition hover:bg-primary-50 hover:text-primary-700" x-text="s.name"></button>
                        </template>
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Harga <span class="text-danger-500">*</span></label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-gray-400">Rp</span>
                        <input type="number" min="0" x-model.number="serviceForm.price" class="block w-full rounded-lg border-gray-300 pl-9 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                    </div>
                </div>
            </div>
            <x-slot:footer>
                <x-ui.button variant="outline" type="button" @click="showAddService = false">Batal</x-ui.button>
                <x-ui.button type="button" icon="plus" ::disabled="! serviceForm.name || serviceForm.price === ''" @click="addService()">Tambah</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>

        {{-- Modal pelunasan / selesai --}}
        <x-ui.modal title="Servis Selesai" size="sm" show="showComplete">
            <p class="text-sm text-gray-600">Catat pembayaran sisa (jika ada), lalu tandai servis selesai.</p>
            <div class="mt-3 rounded-lg bg-gray-50 p-3 text-sm">
                <div class="flex justify-between text-gray-500"><span>Total</span><span x-text="rupiah(order?.total)"></span></div>
                <div class="flex justify-between text-gray-500"><span>Terbayar</span><span x-text="rupiah(order?.paid_amount)"></span></div>
                <div class="flex justify-between font-medium text-gray-800"><span>Sisa</span><span x-text="rupiah(order?.remaining)"></span></div>
            </div>
            <div class="mt-3">
                <label class="mb-1 block text-sm font-medium text-gray-700">Pembayaran Sekarang</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">Rp</span>
                    <input type="number" min="0" x-model.number="paymentInput" class="block w-full rounded-lg border-gray-300 pl-10 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                </div>
            </div>
            <x-slot:footer>
                <x-ui.button variant="outline" type="button" @click="showComplete = false">Batal</x-ui.button>
                <x-ui.button type="button" icon="check" ::disabled="completing" @click="complete()"><span x-text="completing ? 'Memproses...' : 'Selesaikan'"></span></x-ui.button>
            </x-slot:footer>
        </x-ui.modal>

        {{-- Modal batal servis / ubah biaya pembatalan --}}
        <x-ui.modal size="sm" show="showCancel" title="Pembatalan Servis">
            <p class="text-sm font-medium text-gray-800" x-text="cancelMode === 'adjust' ? 'Ubah biaya pembatalan' : 'Batalkan servis ini'"></p>
            <p class="mt-0.5 text-sm text-gray-600" x-show="cancelMode === 'create'">Stok bahan akan dikembalikan ke produk.</p>

            {{-- Biaya pembatalan dinamis (DP ditahan) --}}
            <div class="mt-3">
                <label class="mb-1 block text-sm font-medium text-gray-700">Biaya Pembatalan (DP ditahan)</label>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">Rp</span>
                    <input type="number" min="0" :max="order?.paid_amount" x-model.number="cancelFee"
                        class="block w-full rounded-lg border-gray-300 pl-10 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                </div>
                <div class="mt-1 flex items-center justify-between text-xs text-gray-500">
                    <span>DP dibayar: <span class="font-medium text-gray-700" x-text="rupiah(order?.paid_amount)"></span></span>
                    <span>Dikembalikan: <span class="font-medium text-primary-600" x-text="rupiah(cancelRefund)"></span></span>
                </div>
                <p x-show="Number(cancelFee) > Number(order?.paid_amount || 0)" x-cloak class="mt-1 text-xs text-danger-600">
                    Biaya pembatalan akan dibatasi maksimal sebesar DP yang sudah dibayar.
                </p>
            </div>

            <div class="mt-3">
                <label class="mb-1 block text-sm font-medium text-gray-700">Keterangan <span class="text-danger-500">*</span></label>
                <textarea rows="3" x-model="cancelNote" placeholder="Alasan pembatalan..." class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
            </div>
            <x-slot:footer>
                <x-ui.button variant="outline" type="button" @click="showCancel = false">Kembali</x-ui.button>
                <x-ui.button variant="danger" type="button" icon="check" ::disabled="canceling || ! cancelNote.trim()" @click="cancel()">
                    <span x-text="canceling ? 'Memproses...' : (cancelMode === 'adjust' ? 'Simpan' : 'Ya, Batalkan')"></span>
                </x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    </div>
</x-layouts.app>
