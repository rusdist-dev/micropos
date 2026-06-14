<x-layouts.app title="Order Servis">
    <div
        x-data="{
            loading: true,
            error: null,
            items: [],
            meta: { current_page: 1, last_page: 1, total: 0 },
            search: '',
            serviceStatus: '',
            dateFrom: '',
            dateTo: '',
            page: 1,

            params() {
                const p = new URLSearchParams();
                if (this.search) p.set('search', this.search);
                if (this.serviceStatus) p.set('service_status', this.serviceStatus);
                if (this.dateFrom) p.set('date_from', this.dateFrom);
                if (this.dateTo) p.set('date_to', this.dateTo);
                return p;
            },
            async load() {
                this.loading = true; this.error = null;
                try {
                    const p = this.params();
                    p.set('page', this.page); p.set('per_page', 15);
                    const res = await window.api.get('/api/service-orders?' + p.toString());
                    this.items = res.data; this.meta = res.meta;
                } catch (e) { this.error = e.message; } finally { this.loading = false; }
            },
            goToPage(p) { if (p < 1 || p > this.meta.last_page) return; this.page = p; this.load(); },
            fmtDate(iso) { return iso ? new Date(iso).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '—'; },
            fmtDay(d) { return d ? new Date(d).toLocaleDateString('id-ID', { dateStyle: 'medium' }) : '—'; },
            statusClass(s) {
                return s === 'process' ? 'bg-warning-50 text-warning-700 ring-warning-600/20'
                    : s === 'selesai' ? 'bg-primary-50 text-primary-700 ring-primary-600/20'
                    : 'bg-danger-50 text-danger-700 ring-danger-600/20';
            },
            payClass(s) {
                return s === 'lunas' ? 'bg-primary-50 text-primary-700 ring-primary-600/20'
                    : s === 'dp' ? 'bg-warning-50 text-warning-700 ring-warning-600/20'
                    : 'bg-gray-100 text-gray-500 ring-gray-500/20';
            },
        }"
        x-init="load(); $watch('search', () => { page = 1; load(); }); $watch('serviceStatus', () => { page = 1; load(); }); $watch('dateFrom', () => { page = 1; load(); }); $watch('dateTo', () => { page = 1; load(); })"
    >
        <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:flex lg:items-end">
                <div class="lg:w-56">
                    <label class="mb-1 block text-xs font-medium text-gray-500">Cari</label>
                    <x-ui.search-input placeholder="Invoice / pelanggan..." model="search" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">Status</label>
                    <select x-model="serviceStatus" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 lg:w-40">
                        <option value="">Semua Status</option>
                        <option value="process">Proses</option>
                        <option value="selesai">Selesai</option>
                        <option value="batal">Batal</option>
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
            @can('service-orders.create')
                <a href="{{ route('service-orders.create') }}">
                    <x-ui.button type="button" icon="plus">Mulai Servis</x-ui.button>
                </a>
            @endcan
        </div>

        <div x-show="loading" class="py-10"><x-ui.loading-spinner size="lg" label="Memuat order servis..." /></div>
        <template x-if="error"><x-ui.alert variant="danger" title="Gagal memuat"><span x-text="error"></span></x-ui.alert></template>

        <div x-show="!loading && !error && items.length === 0" class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-gray-400">
            Tidak ada order servis pada rentang ini.
        </div>

        <div x-show="!loading && !error && items.length > 0" class="overflow-x-auto">
            <x-ui.table>
                <x-slot:head>
                    <x-ui.th>No Invoice</x-ui.th>
                    <x-ui.th>Tgl Transaksi</x-ui.th>
                    <x-ui.th>Tenggang Waktu</x-ui.th>
                    <x-ui.th>Tgl Selesai</x-ui.th>
                    <x-ui.th>Pelanggan</x-ui.th>
                    <x-ui.th>Teknisi</x-ui.th>
                    <x-ui.th>Operator</x-ui.th>
                    <x-ui.th align="center">Status</x-ui.th>
                    <x-ui.th align="right">Total</x-ui.th>
                    <x-ui.th align="right">Aksi</x-ui.th>
                </x-slot:head>
                <template x-for="o in items" :key="o.id">
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-1 text-sm font-medium text-primary-700" x-text="o.invoice_number"></td>
                        <td class="px-4 py-1 text-sm text-gray-500" x-text="fmtDate(o.created_at)"></td>
                        <td class="px-4 py-1 text-sm text-gray-500" x-text="fmtDay(o.due_date)"></td>
                        <td class="px-4 py-1 text-sm text-gray-500" x-text="fmtDate(o.completed_at)"></td>
                        <td class="px-4 py-1 text-sm text-gray-700" x-text="o.customer_name || 'Pelanggan Umum'"></td>
                        <td class="px-4 py-1 text-sm text-gray-500" x-text="o.technician_name || '—'"></td>
                        <td class="px-4 py-1 text-sm text-gray-500" x-text="o.operator_name || '—'"></td>
                        <td class="px-4 py-1 text-center">
                            <div class="flex flex-col items-center gap-1">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset" :class="statusClass(o.service_status)" x-text="o.service_status_label"></span>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium ring-1 ring-inset" :class="payClass(o.payment_status)" x-text="o.payment_status_label"></span>
                            </div>
                        </td>
                        <td class="px-4 py-1 text-right text-sm font-semibold text-gray-800" x-text="window.rupiah(o.total)"></td>
                        <td class="px-4 py-1 text-right">
                            <a :href="'{{ url('service-orders') }}/' + o.id" class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 hover:underline">
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
