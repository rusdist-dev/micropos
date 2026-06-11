<x-layouts.app title="Pengguna">
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
                    const res = await window.api.get('/api/users?' + params.toString());
                    this.items = res.data; this.meta = res.meta;
                } catch (e) { this.error = e.message; } finally { this.loading = false; }
            },
            goToPage(p) { if (p < 1 || p > this.meta.last_page) return; this.page = p; this.load(); },
            askDelete(item) { this.deleting = item; this.showDelete = true; },
            async doDelete() {
                try {
                    const res = await window.api.delete('/api/users/' + this.deleting.id);
                    this.$store.toasts.success(res.message || 'Pengguna dihapus');
                    this.showDelete = false; this.load();
                } catch (e) { this.showDelete = false; this.$store.toasts.error(e.message); }
            },
            roleClass(role) {
                return role === 'admin' ? 'bg-primary-50 text-primary-700 ring-primary-600/20' : 'bg-warning-50 text-warning-700 ring-warning-600/20';
            },
        }"
        x-init="$nextTick(() => { const f = window.flash.pop(); if (f) $store.toasts.push(f.message, f.type); }); load(); $watch('search', () => { page = 1; load(); })"
    >
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="sm:w-80"><x-ui.search-input placeholder="Cari nama atau email..." model="search" /></div>
            <x-ui.button :href="route('users.create')" icon="user-plus">Tambah Pengguna</x-ui.button>
        </div>

        <div x-show="loading" class="py-10"><x-ui.loading-spinner size="lg" label="Memuat pengguna..." /></div>
        <template x-if="error"><x-ui.alert variant="danger" title="Gagal memuat"><span x-text="error"></span></x-ui.alert></template>

        <div x-show="!loading && !error && items.length === 0" class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-gray-400">
            Tidak ada pengguna yang cocok.
        </div>

        <div x-show="!loading && !error && items.length > 0">
            <x-ui.table>
                <x-slot:head>
                    <x-ui.th>Nama</x-ui.th>
                    <x-ui.th>Email</x-ui.th>
                    <x-ui.th align="center">Role</x-ui.th>
                    <x-ui.th align="center">Status</x-ui.th>
                    <x-ui.th align="right">Aksi</x-ui.th>
                </x-slot:head>
                <template x-for="u in items" :key="u.id">
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-1">
                            <div class="flex items-center gap-3">
                                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-500 text-sm font-semibold text-white" x-text="u.name.charAt(0).toUpperCase()"></span>
                                <span class="text-sm font-medium text-gray-800" x-text="u.name"></span>
                            </div>
                        </td>
                        <td class="px-4 py-1 text-sm text-gray-500" x-text="u.email"></td>
                        <td class="px-4 py-1 text-center">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ring-1 ring-inset" :class="roleClass(u.role)" x-text="u.role || '—'"></span>
                        </td>
                        <td class="px-4 py-1 text-center">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset"
                                :class="u.is_active ? 'bg-primary-50 text-primary-700 ring-primary-600/20' : 'bg-gray-100 text-gray-500 ring-gray-500/20'"
                                x-text="u.is_active ? 'Aktif' : 'Nonaktif'"></span>
                        </td>
                        <td class="px-4 py-1">
                            <div class="flex items-center justify-end gap-1">
                                <a :href="'{{ url('users') }}/' + u.id + '/edit'" class="rounded-md p-1.5 text-gray-400 transition hover:bg-primary-50 hover:text-primary-600" title="Edit"><x-heroicon-o-pencil-square class="h-4 w-4" /></a>
                                <button type="button" @click="askDelete(u)" class="rounded-md p-1.5 text-gray-400 transition hover:bg-danger-50 hover:text-danger-600" title="Hapus"><x-heroicon-o-trash class="h-4 w-4" /></button>
                            </div>
                        </td>
                    </tr>
                </template>
                <x-slot:footer><x-ui.pagination page="meta.current_page" lastPage="meta.last_page" total="meta.total" /></x-slot:footer>
            </x-ui.table>
        </div>

        <x-ui.modal title="Hapus Pengguna" size="sm" show="showDelete">
            <p class="text-sm text-gray-600">Yakin menghapus pengguna <span class="font-semibold" x-text="deleting?.name"></span>?</p>
            <x-slot:footer>
                <x-ui.button variant="outline" type="button" @click="showDelete = false">Batal</x-ui.button>
                <x-ui.button variant="danger" type="button" icon="trash" @click="doDelete()">Hapus</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    </div>
</x-layouts.app>
