<x-layouts.app title="Edit Produk" :breadcrumbs="['Produk' => route('products.index'), 'Edit' => null]">
    @include('pages.products._form', ['productId' => $id])

    {{-- Riwayat pergerakan stok (kartu stok) --}}
    <div
        class="mt-6"
        x-data="{
            id: {{ (int) $id }},
            loading: true, error: null, items: [],
            meta: { current_page: 1, last_page: 1, total: 0 },
            type: '', page: 1,
            async load() {
                this.loading = true; this.error = null;
                try {
                    const params = new URLSearchParams({ page: this.page, per_page: 10 });
                    if (this.type) params.set('type', this.type);
                    const res = await window.api.get('/api/products/' + this.id + '/stock-movements?' + params.toString());
                    this.items = res.data; this.meta = res.meta;
                } catch (e) { this.error = e.message; } finally { this.loading = false; }
            },
            goToPage(p) { if (p < 1 || p > this.meta.last_page) return; this.page = p; this.load(); },
            fmtDate(iso) { return iso ? new Date(iso).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '—'; },
            badgeClass(t) {
                return {
                    sale: 'bg-gray-100 text-gray-600 ring-gray-500/20',
                    supply: 'bg-primary-50 text-primary-700 ring-primary-600/20',
                    opname: 'bg-warning-50 text-warning-700 ring-warning-600/20',
                    return_in: 'bg-primary-50 text-primary-700 ring-primary-600/20',
                    return_out: 'bg-danger-50 text-danger-700 ring-danger-600/20',
                    adjustment: 'bg-warning-50 text-warning-700 ring-warning-600/20',
                }[t] || 'bg-gray-100 text-gray-600 ring-gray-500/20';
            },
        }"
        x-init="load(); $watch('type', () => { page = 1; load(); })"
    >
        <x-ui.card title="Riwayat Stok">
            <x-slot:actions>
                <select x-model="type" class="rounded-lg border-gray-300 py-1 text-xs shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">Semua</option>
                    <option value="sale">Penjualan</option>
                    <option value="supply">Supply</option>
                    <option value="opname">Opname</option>
                    <option value="return_in">Retur Masuk</option>
                    <option value="return_out">Penukaran</option>
                </select>
            </x-slot:actions>

            <div x-show="loading" class="py-6"><x-ui.loading-spinner label="Memuat riwayat..." /></div>
            <template x-if="error"><x-ui.alert variant="danger"><span x-text="error"></span></x-ui.alert></template>
            <div x-show="!loading && !error && items.length === 0" class="py-6 text-center text-sm text-gray-400">Belum ada pergerakan stok.</div>

            <div x-show="!loading && !error && items.length > 0" class="-mx-6 -mb-6">
                <x-ui.table>
                    <x-slot:head>
                        <x-ui.th>Tanggal</x-ui.th>
                        <x-ui.th>Tipe</x-ui.th>
                        <x-ui.th align="center">Perubahan</x-ui.th>
                        <x-ui.th align="center">Stok</x-ui.th>
                        <x-ui.th>Oleh</x-ui.th>
                        <x-ui.th>Catatan</x-ui.th>
                    </x-slot:head>
                    <template x-for="m in items" :key="m.id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-500" x-text="fmtDate(m.created_at)"></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset" :class="badgeClass(m.type)" x-text="m.type_label"></span>
                            </td>
                            <td class="px-4 py-3 text-center text-sm font-semibold" :class="m.qty_change >= 0 ? 'text-primary-600' : 'text-danger-600'"
                                x-text="(m.qty_change > 0 ? '+' : '') + m.qty_change"></td>
                            <td class="px-4 py-3 text-center text-xs text-gray-500"><span x-text="m.stock_before"></span> &rarr; <span class="font-medium text-gray-700" x-text="m.stock_after"></span></td>
                            <td class="px-4 py-3 text-sm text-gray-500" x-text="m.user_name || '—'"></td>
                            <td class="px-4 py-3 text-sm text-gray-500" x-text="m.note || '—'"></td>
                        </tr>
                    </template>
                    <x-slot:footer><x-ui.pagination page="meta.current_page" lastPage="meta.last_page" total="meta.total" /></x-slot:footer>
                </x-ui.table>
            </div>
        </x-ui.card>
    </div>
</x-layouts.app>
