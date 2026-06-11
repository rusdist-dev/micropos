<x-layouts.app title="Pelanggan">
    <div
        x-data="{
            loading: true,
            error: null,
            items: [],
            meta: { current_page: 1, last_page: 1, total: 0 },
            search: '',
            page: 1,
            deleting: null,
            showDelete: false,

            async load() {
                this.loading = true; this.error = null;
                try {
                    const params = new URLSearchParams({ page: this.page, per_page: 10 });
                    if (this.search) params.set('search', this.search);
                    const res = await window.api.get('/api/customers?' + params.toString());
                    this.items = res.data; this.meta = res.meta;
                } catch (e) { this.error = e.message; } finally { this.loading = false; }
            },
            goToPage(p) { if (p < 1 || p > this.meta.last_page) return; this.page = p; this.load(); },
            askDelete(item) { this.deleting = item; this.showDelete = true; },
            async doDelete() {
                try {
                    const res = await window.api.delete('/api/customers/' + this.deleting.id);
                    this.$store.toasts.success(res.message || 'Pelanggan dihapus');
                    this.showDelete = false;
                    if (this.items.length === 1 && this.page > 1) this.page--;
                    this.load();
                } catch (e) { this.showDelete = false; this.$store.toasts.error(e.message); }
            },
        }"
        x-init="$nextTick(() => { const f = window.flash.pop(); if (f) $store.toasts.push(f.message, f.type); }); load(); $watch('search', () => { page = 1; load(); })"
    >
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="sm:w-80"><x-ui.search-input placeholder="Cari nama atau telepon..." model="search" /></div>
            <x-ui.button :href="route('customers.create')" icon="plus">Tambah Pelanggan</x-ui.button>
        </div>

        <div x-show="loading" class="py-10"><x-ui.loading-spinner size="lg" label="Memuat pelanggan..." /></div>
        <template x-if="error"><x-ui.alert variant="danger" title="Gagal memuat"><span x-text="error"></span></x-ui.alert></template>

        <div x-show="!loading && !error && items.length === 0" class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-gray-400">
            Tidak ada pelanggan yang cocok.
        </div>

        <div x-show="!loading && !error && items.length > 0">
            <x-ui.table>
                <x-slot:head>
                    <x-ui.th>Nama</x-ui.th>
                    <x-ui.th>Telepon</x-ui.th>
                    <x-ui.th>Alamat</x-ui.th>
                    <x-ui.th align="right">Aksi</x-ui.th>
                </x-slot:head>
                <template x-for="c in items" :key="c.id">
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-1">
                            <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-50 text-sm font-semibold text-primary-600" x-text="c.name.charAt(0).toUpperCase()"></span>
                                <span class="text-sm font-medium text-gray-800" x-text="c.name"></span>
                            </div>
                        </td>
                        <td class="px-4 py-1 text-sm text-gray-500" x-text="c.phone || '—'"></td>
                        <td class="px-4 py-1 text-sm text-gray-500" x-text="c.address || '—'"></td>
                        <td class="px-4 py-1">
                            <div class="flex items-center justify-end gap-1">
                                <a :href="'{{ url('customers') }}/' + c.id + '/edit'" class="rounded-md p-1.5 text-gray-400 transition hover:bg-primary-50 hover:text-primary-600" title="Edit"><x-heroicon-o-pencil-square class="h-4 w-4" /></a>
                                <button type="button" @click="askDelete(c)" class="rounded-md p-1.5 text-gray-400 transition hover:bg-danger-50 hover:text-danger-600" title="Hapus"><x-heroicon-o-trash class="h-4 w-4" /></button>
                            </div>
                        </td>
                    </tr>
                </template>
                <x-slot:footer><x-ui.pagination page="meta.current_page" lastPage="meta.last_page" total="meta.total" /></x-slot:footer>
            </x-ui.table>
        </div>

        <x-ui.modal title="Hapus Pelanggan" size="sm" show="showDelete">
            <p class="text-sm text-gray-600">Yakin menghapus pelanggan <span class="font-semibold" x-text="deleting?.name"></span>?</p>
            <x-slot:footer>
                <x-ui.button variant="outline" type="button" @click="showDelete = false">Batal</x-ui.button>
                <x-ui.button variant="danger" type="button" icon="trash" @click="doDelete()">Hapus</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    </div>
</x-layouts.app>
