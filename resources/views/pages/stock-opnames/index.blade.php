<x-layouts.app title="Stok Opname">
    <div
        x-data="{
            loading: true, error: null, items: [],
            meta: { current_page: 1, last_page: 1, total: 0 },
            search: '', status: '', page: 1,
            async load() {
                this.loading = true; this.error = null;
                try {
                    const params = new URLSearchParams({ page: this.page, per_page: 15 });
                    if (this.search) params.set('search', this.search);
                    if (this.status) params.set('status', this.status);
                    const res = await window.api.get('/api/stock-opnames?' + params.toString());
                    this.items = res.data; this.meta = res.meta;
                } catch (e) { this.error = e.message; } finally { this.loading = false; }
            },
            goToPage(p) { if (p < 1 || p > this.meta.last_page) return; this.page = p; this.load(); },
            fmtDate(iso) { return iso ? new Date(iso).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '—'; },
        }"
        x-init="$nextTick(() => { const f = window.flash.pop(); if (f) $store.toasts.push(f.message, f.type); }); load(); $watch('search', () => { page = 1; load(); }); $watch('status', () => { page = 1; load(); })"
    >
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-1 flex-col gap-2 sm:flex-row">
                <div class="sm:w-64"><x-ui.search-input placeholder="Cari kode opname..." model="search" /></div>
                <select x-model="status" class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:w-40">
                    <option value="">Semua Status</option>
                    <option value="draft">Draft</option>
                    <option value="completed">Selesai</option>
                </select>
            </div>
            <x-ui.button :href="route('stock-opnames.create')" icon="plus">Opname Baru</x-ui.button>
        </div>

        <div x-show="loading" class="py-10"><x-ui.loading-spinner size="lg" label="Memuat..." /></div>
        <template x-if="error"><x-ui.alert variant="danger" title="Gagal memuat"><span x-text="error"></span></x-ui.alert></template>

        <div x-show="!loading && !error && items.length === 0" class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-gray-400">Belum ada sesi opname.</div>

        <div x-show="!loading && !error && items.length > 0">
            <x-ui.table>
                <x-slot:head>
                    <x-ui.th>Kode</x-ui.th>
                    <x-ui.th>Tanggal</x-ui.th>
                    <x-ui.th>Petugas</x-ui.th>
                    <x-ui.th align="center">Item</x-ui.th>
                    <x-ui.th align="center">Status</x-ui.th>
                    <x-ui.th align="right">Aksi</x-ui.th>
                </x-slot:head>
                <template x-for="o in items" :key="o.id">
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-1 text-sm font-medium text-primary-700" x-text="o.code"></td>
                        <td class="px-4 py-1 text-sm text-gray-500" x-text="fmtDate(o.created_at)"></td>
                        <td class="px-4 py-1 text-sm text-gray-500" x-text="o.user_name"></td>
                        <td class="px-4 py-1 text-center text-sm text-gray-500" x-text="o.items_count"></td>
                        <td class="px-4 py-1 text-center">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset"
                                :class="o.status === 'completed' ? 'bg-primary-50 text-primary-700 ring-primary-600/20' : 'bg-warning-50 text-warning-700 ring-warning-600/20'"
                                x-text="o.status_label"></span>
                        </td>
                        <td class="px-4 py-1 text-right">
                            <a :href="'{{ url('stock-opnames') }}/' + o.id" class="inline-flex items-center gap-1 text-sm font-medium text-primary-600 hover:underline">
                                <span x-text="o.status === 'draft' ? 'Lanjutkan' : 'Detail'"></span> <x-heroicon-o-chevron-right class="h-4 w-4" />
                            </a>
                        </td>
                    </tr>
                </template>
                <x-slot:footer><x-ui.pagination page="meta.current_page" lastPage="meta.last_page" total="meta.total" /></x-slot:footer>
            </x-ui.table>
        </div>
    </div>
</x-layouts.app>
