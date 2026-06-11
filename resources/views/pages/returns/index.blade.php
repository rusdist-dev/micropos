<x-layouts.app title="Retur Barang">
    <div
        x-data="{
            loading: true, error: null, items: [],
            meta: { current_page: 1, last_page: 1, total: 0 },
            search: '', page: 1,
            async load() {
                this.loading = true; this.error = null;
                try {
                    const params = new URLSearchParams({ page: this.page, per_page: 15 });
                    if (this.search) params.set('search', this.search);
                    const res = await window.api.get('/api/returns?' + params.toString());
                    this.items = res.data; this.meta = res.meta;
                } catch (e) { this.error = e.message; } finally { this.loading = false; }
            },
            goToPage(p) { if (p < 1 || p > this.meta.last_page) return; this.page = p; this.load(); },
            fmtDate(iso) { return iso ? new Date(iso).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '—'; },
        }"
        x-init="$nextTick(() => { const f = window.flash.pop(); if (f) $store.toasts.push(f.message, f.type); }); load(); $watch('search', () => { page = 1; load(); })"
    >
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="sm:w-80"><x-ui.search-input placeholder="Cari kode retur / invoice..." model="search" /></div>
            <x-ui.button :href="route('returns.create')" icon="arrow-uturn-left">Retur Baru</x-ui.button>
        </div>

        <div x-show="loading" class="py-10"><x-ui.loading-spinner size="lg" label="Memuat..." /></div>
        <template x-if="error"><x-ui.alert variant="danger" title="Gagal memuat"><span x-text="error"></span></x-ui.alert></template>

        <div x-show="!loading && !error && items.length === 0" class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-gray-400">Belum ada transaksi retur.</div>

        <div x-show="!loading && !error && items.length > 0">
            <x-ui.table>
                <x-slot:head>
                    <x-ui.th>Kode</x-ui.th>
                    <x-ui.th>Invoice Asal</x-ui.th>
                    <x-ui.th>Tanggal</x-ui.th>
                    <x-ui.th align="right">Nilai Retur</x-ui.th>
                    <x-ui.th align="right">Nilai Tukar</x-ui.th>
                    <x-ui.th align="right">Selisih</x-ui.th>
                    <x-ui.th align="right">Aksi</x-ui.th>
                </x-slot:head>
                <template x-for="r in items" :key="r.id">
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-1 text-sm font-medium text-primary-700" x-text="r.code"></td>
                        <td class="px-4 py-1 text-sm text-gray-700" x-text="r.invoice_number"></td>
                        <td class="px-4 py-1 text-sm text-gray-500" x-text="fmtDate(r.created_at)"></td>
                        <td class="px-4 py-1 text-right text-sm text-gray-700" x-text="window.rupiah(r.returned_total)"></td>
                        <td class="px-4 py-1 text-right text-sm text-gray-700" x-text="window.rupiah(r.exchange_total)"></td>
                        <td class="px-4 py-1 text-right text-sm font-semibold"
                            :class="r.balance > 0 ? 'text-gray-800' : (r.balance < 0 ? 'text-danger-600' : 'text-gray-500')"
                            x-text="(r.balance < 0 ? '-' : '') + window.rupiah(Math.abs(r.balance))"></td>
                        <td class="px-4 py-1 text-right">
                            <a :href="'{{ url('returns') }}/' + r.id" class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 hover:underline">Detail <x-heroicon-o-chevron-right class="h-4 w-4" /></a>
                        </td>
                    </tr>
                </template>
                <x-slot:footer><x-ui.pagination page="meta.current_page" lastPage="meta.last_page" total="meta.total" /></x-slot:footer>
            </x-ui.table>
        </div>
    </div>
</x-layouts.app>
