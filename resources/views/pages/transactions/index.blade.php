<x-layouts.app title="Riwayat Transaksi">
    <div
        x-data="{
            loading: true,
            error: null,
            items: [],
            meta: { current_page: 1, last_page: 1, total: 0 },
            search: '',
            dateFrom: '',
            dateTo: '',
            kasirId: '',
            page: 1,
            canViewAll: @json(auth()->user()?->can('transactions.view-all') ?? false),
            kasirs: [],
            exporting: false,

            params() {
                const p = new URLSearchParams();
                if (this.search) p.set('search', this.search);
                if (this.dateFrom) p.set('date_from', this.dateFrom);
                if (this.dateTo) p.set('date_to', this.dateTo);
                if (this.kasirId) p.set('kasir_id', this.kasirId);
                return p;
            },
            async load() {
                this.loading = true; this.error = null;
                try {
                    const p = this.params();
                    p.set('page', this.page); p.set('per_page', 15);
                    const res = await window.api.get('/api/transactions?' + p.toString());
                    this.items = res.data; this.meta = res.meta;
                } catch (e) { this.error = e.message; } finally { this.loading = false; }
            },
            async loadKasirs() {
                if (! this.canViewAll) return;
                try {
                    const res = await window.api.get('/api/transactions/kasirs');
                    this.kasirs = res.data;
                } catch (e) { /* abaikan */ }
            },
            exportExcel() {
                this.exporting = true;
                // Unduh via navigasi (cookie sesi terkirim). Hormati filter aktif.
                window.location.href = '/api/transactions/export?' + this.params().toString();
                setTimeout(() => { this.exporting = false; }, 1500);
            },
            goToPage(p) { if (p < 1 || p > this.meta.last_page) return; this.page = p; this.load(); },
            fmtDate(iso) {
                if (! iso) return '—';
                return new Date(iso).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
            },
        }"
        x-init="load(); loadKasirs(); $watch('search', () => { page = 1; load(); }); $watch('dateFrom', () => { page = 1; load(); }); $watch('dateTo', () => { page = 1; load(); }); $watch('kasirId', () => { page = 1; load(); })"
    >
        <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:flex lg:items-end">
                <div class="lg:w-56">
                    <label class="mb-1 block text-xs font-medium text-gray-500">Cari</label>
                    <x-ui.search-input placeholder="Invoice / pelanggan..." model="search" />
                </div>
                <div x-show="canViewAll">
                    <label class="mb-1 block text-xs font-medium text-gray-500">Kasir</label>
                    <select x-model="kasirId" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 lg:w-44">
                        <option value="">Semua Kasir</option>
                        <template x-for="k in kasirs" :key="k.id"><option :value="k.id" x-text="k.name"></option></template>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">Dari Tanggal</label>
                    <input type="date" x-model="dateFrom" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">Sampai Tanggal</label>
                    <input type="date" x-model="dateTo" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                </div>
            </div>
            <x-ui.button variant="outline" type="button" icon="arrow-down-tray" ::disabled="exporting" @click="exportExcel()">
                <span x-text="exporting ? 'Menyiapkan...' : 'Export Excel'"></span>
            </x-ui.button>
        </div>

        <div x-show="loading" class="py-10"><x-ui.loading-spinner size="lg" label="Memuat transaksi..." /></div>
        <template x-if="error"><x-ui.alert variant="danger" title="Gagal memuat"><span x-text="error"></span></x-ui.alert></template>

        <div x-show="!loading && !error && items.length === 0" class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-gray-400">
            Tidak ada transaksi pada rentang ini.
        </div>

        <div x-show="!loading && !error && items.length > 0">
            <x-ui.table>
                <x-slot:head>
                    <x-ui.th>Invoice</x-ui.th>
                    <x-ui.th>Tanggal</x-ui.th>
                    <x-ui.th>Pelanggan</x-ui.th>
                    <x-ui.th>Kasir</x-ui.th>
                    <x-ui.th align="center">Item</x-ui.th>
                    <x-ui.th align="right">Total</x-ui.th>
                    <x-ui.th align="right">Aksi</x-ui.th>
                </x-slot:head>
                <template x-for="t in items" :key="t.id">
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-1 text-sm font-medium text-primary-700" x-text="t.invoice_number"></td>
                        <td class="px-4 py-1 text-sm text-gray-500" x-text="fmtDate(t.created_at)"></td>
                        <td class="px-4 py-1 text-sm text-gray-700" x-text="t.customer_name || 'Pelanggan Umum'"></td>
                        <td class="px-4 py-1 text-sm text-gray-500" x-text="t.kasir_name"></td>
                        <td class="px-4 py-1 text-center text-sm text-gray-500" x-text="t.items_count"></td>
                        <td class="px-4 py-1 text-right text-sm font-semibold text-gray-800" x-text="window.rupiah(t.total)"></td>
                        <td class="px-4 py-1 text-right">
                            <a :href="'{{ url('transactions') }}/' + t.id" class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 hover:underline">
                                Detail <x-heroicon-o-chevron-right class="h-4 w-4" />
                            </a>
                        </td>
                    </tr>
                </template>
                <x-slot:footer><x-ui.pagination page="meta.current_page" lastPage="meta.last_page" total="meta.total" /></x-slot:footer>
            </x-ui.table>
        </div>
    </div>
</x-layouts.app>
