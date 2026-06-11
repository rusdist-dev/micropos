<x-layouts.app title="Supply Barang">
    <div
        x-data="{
            loading: true, error: null, items: [],
            meta: { current_page: 1, last_page: 1, total: 0 },
            search: '', dateFrom: '', dateTo: '', page: 1,
            async load() {
                this.loading = true; this.error = null;
                try {
                    const params = new URLSearchParams({ page: this.page, per_page: 15 });
                    if (this.search) params.set('search', this.search);
                    if (this.dateFrom) params.set('date_from', this.dateFrom);
                    if (this.dateTo) params.set('date_to', this.dateTo);
                    const res = await window.api.get('/api/supplies?' + params.toString());
                    this.items = res.data; this.meta = res.meta;
                } catch (e) { this.error = e.message; } finally { this.loading = false; }
            },
            goToPage(p) { if (p < 1 || p > this.meta.last_page) return; this.page = p; this.load(); },
            fmtDate(iso) { return iso ? new Date(iso).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '—'; },
        }"
        x-init="$nextTick(() => { const f = window.flash.pop(); if (f) $store.toasts.push(f.message, f.type); }); load(); $watch('search', () => { page = 1; load(); }); $watch('dateFrom', () => { page = 1; load(); }); $watch('dateTo', () => { page = 1; load(); })"
    >
        <div class="mb-4 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 lg:flex lg:items-end">
                <div class="lg:w-64"><label class="mb-1 block text-xs font-medium text-gray-500">Cari</label><x-ui.search-input placeholder="Kode / pemasok..." model="search" /></div>
                <div><label class="mb-1 block text-xs font-medium text-gray-500">Dari</label><input type="date" x-model="dateFrom" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" /></div>
                <div><label class="mb-1 block text-xs font-medium text-gray-500">Sampai</label><input type="date" x-model="dateTo" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" /></div>
            </div>
            <x-ui.button :href="route('supplies.create')" icon="plus">Supply Baru</x-ui.button>
        </div>

        <div x-show="loading" class="py-10"><x-ui.loading-spinner size="lg" label="Memuat..." /></div>
        <template x-if="error"><x-ui.alert variant="danger" title="Gagal memuat"><span x-text="error"></span></x-ui.alert></template>

        <div x-show="!loading && !error && items.length === 0" class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-gray-400">Belum ada transaksi supply.</div>

        <div x-show="!loading && !error && items.length > 0">
            <x-ui.table>
                <x-slot:head>
                    <x-ui.th>Kode</x-ui.th>
                    <x-ui.th>Tanggal</x-ui.th>
                    <x-ui.th>Pemasok</x-ui.th>
                    <x-ui.th>Petugas</x-ui.th>
                    <x-ui.th align="center">Item</x-ui.th>
                    <x-ui.th align="right">Total Modal</x-ui.th>
                    <x-ui.th align="right">Aksi</x-ui.th>
                </x-slot:head>
                <template x-for="s in items" :key="s.id">
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-1 text-sm font-medium text-primary-700" x-text="s.code"></td>
                        <td class="px-4 py-1 text-sm text-gray-500" x-text="fmtDate(s.created_at)"></td>
                        <td class="px-4 py-1 text-sm text-gray-700" x-text="s.supplier_name"></td>
                        <td class="px-4 py-1 text-sm text-gray-500" x-text="s.user_name"></td>
                        <td class="px-4 py-1 text-center text-sm text-gray-500" x-text="s.items_count"></td>
                        <td class="px-4 py-1 text-right text-sm font-semibold text-gray-800" x-text="window.rupiah(s.total_cost)"></td>
                        <td class="px-4 py-1 text-right">
                            <a :href="'{{ url('supplies') }}/' + s.id" class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 hover:underline">Detail <x-heroicon-o-chevron-right class="h-4 w-4" /></a>
                        </td>
                    </tr>
                </template>
                <x-slot:footer><x-ui.pagination page="meta.current_page" lastPage="meta.last_page" total="meta.total" /></x-slot:footer>
            </x-ui.table>
        </div>
    </div>
</x-layouts.app>
