<x-layouts.app title="Detail Opname" :breadcrumbs="['Stok Opname' => route('stock-opnames.index'), 'Detail' => null]">
    <div
        x-data="{
            id: {{ (int) $id }},
            loading: true, error: null, saving: false, finalizing: false,
            opname: null, rows: [], search: '', showFinalize: false,

            get isDraft() { return this.opname?.status === 'draft'; },
            get filteredRows() {
                const q = this.search.toLowerCase();
                return this.rows.filter(r => !q || r.product_name.toLowerCase().includes(q) || (r.sku && r.sku.toLowerCase().includes(q)));
            },
            get countedCount() { return this.rows.filter(r => r.counted_qty !== '' && r.counted_qty !== null).length; },
            diff(r) {
                if (r.counted_qty === '' || r.counted_qty === null) return null;
                return Number(r.counted_qty) - r.system_qty;
            },
            fmtDate(iso) { return iso ? new Date(iso).toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'short' }) : '—'; },

            async load() {
                this.loading = true; this.error = null;
                try {
                    const res = await window.api.get('/api/stock-opnames/' + this.id);
                    this.opname = res.data;
                    this.rows = res.data.items.map(it => ({
                        product_id: it.product_id, product_name: it.product_name, sku: it.sku,
                        system_qty: it.system_qty, counted_qty: it.counted_qty ?? '', difference: it.difference, note: it.note ?? '',
                    }));
                } catch (e) { this.error = e.message; } finally { this.loading = false; }
            },
            payload() {
                return { items: this.rows.map(r => ({ product_id: r.product_id, counted_qty: r.counted_qty === '' ? null : Number(r.counted_qty), note: r.note || null })) };
            },
            async saveDraft() {
                this.saving = true;
                try {
                    await window.api.put('/api/stock-opnames/' + this.id, this.payload());
                    this.$store.toasts.success('Hitungan tersimpan.');
                } catch (e) { this.$store.toasts.error(e.message); } finally { this.saving = false; }
            },
            async finalize() {
                this.finalizing = true;
                try {
                    await window.api.put('/api/stock-opnames/' + this.id, this.payload());
                    await window.api.post('/api/stock-opnames/' + this.id + '/finalize');
                    this.showFinalize = false;
                    this.$store.toasts.success('Opname difinalisasi & stok tersinkron.');
                    await this.load();
                } catch (e) { this.$store.toasts.error(e.message); } finally { this.finalizing = false; }
            },
        }"
        x-init="load()"
    >
        <div x-show="loading" class="py-10"><x-ui.loading-spinner size="lg" label="Memuat..." /></div>
        <template x-if="error"><x-ui.alert variant="danger" title="Gagal memuat"><span x-text="error"></span></x-ui.alert></template>

        <div x-show="!loading && opname">
            {{-- Header --}}
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-lg font-semibold text-gray-900" x-text="opname?.code"></span>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset"
                            :class="isDraft ? 'bg-warning-50 text-warning-700 ring-warning-600/20' : 'bg-primary-50 text-primary-700 ring-primary-600/20'"
                            x-text="opname?.status_label"></span>
                    </div>
                    <p class="mt-0.5 text-sm text-gray-500">
                        <span x-text="opname?.user_name"></span> &middot; <span x-text="fmtDate(opname?.created_at)"></span>
                    </p>
                </div>
                <div class="flex items-center gap-2" x-show="isDraft">
                    <div class="w-48"><x-ui.search-input placeholder="Cari produk..." model="search" /></div>
                    <x-ui.button variant="outline" type="button" icon="bookmark" ::disabled="saving" @click="saveDraft()"><span x-text="saving ? '...' : 'Simpan Draft'"></span></x-ui.button>
                    <x-ui.button type="button" icon="check" @click="showFinalize = true">Finalisasi</x-ui.button>
                </div>
            </div>

            <x-ui.alert x-show="isDraft" variant="info" class="mb-4">
                Masukkan jumlah hasil hitung fisik pada kolom <b>Dihitung</b>. Baris yang dikosongkan tidak akan diubah saat finalisasi.
                Sudah dihitung: <span x-text="countedCount"></span> dari <span x-text="rows.length"></span> produk.
            </x-ui.alert>

            <x-ui.table>
                <x-slot:head>
                    <x-ui.th>Produk</x-ui.th>
                    <x-ui.th align="center">Stok Sistem</x-ui.th>
                    <x-ui.th align="center">Dihitung</x-ui.th>
                    <x-ui.th align="center">Selisih</x-ui.th>
                    <x-ui.th>Catatan</x-ui.th>
                </x-slot:head>
                <template x-for="r in filteredRows" :key="r.product_id">
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-1">
                            <p class="text-sm font-medium text-gray-800" x-text="r.product_name"></p>
                            <p class="text-xs text-gray-400" x-text="r.sku || ''"></p>
                        </td>
                        <td class="px-4 py-1 text-center text-sm text-gray-600" x-text="r.system_qty"></td>
                        <td class="px-4 py-1 text-center">
                            <template x-if="isDraft">
                                <input type="number" min="0" x-model="r.counted_qty" class="w-20 rounded-lg border-gray-300 text-center text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                            </template>
                            <template x-if="!isDraft">
                                <span class="text-sm text-gray-700" x-text="r.counted_qty === '' ? '—' : r.counted_qty"></span>
                            </template>
                        </td>
                        <td class="px-4 py-1 text-center">
                            <template x-if="isDraft">
                                <span class="text-sm font-medium" :class="diff(r) === null ? 'text-gray-300' : (diff(r) === 0 ? 'text-gray-500' : (diff(r) > 0 ? 'text-primary-600' : 'text-danger-600'))"
                                    x-text="diff(r) === null ? '—' : (diff(r) > 0 ? '+' + diff(r) : diff(r))"></span>
                            </template>
                            <template x-if="!isDraft">
                                <span class="text-sm font-medium" :class="r.difference === 0 ? 'text-gray-500' : (r.difference > 0 ? 'text-primary-600' : 'text-danger-600')"
                                    x-text="r.counted_qty === '' ? '—' : (r.difference > 0 ? '+' + r.difference : r.difference)"></span>
                            </template>
                        </td>
                        <td class="px-4 py-1">
                            <template x-if="isDraft">
                                <input type="text" x-model="r.note" placeholder="—" class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                            </template>
                            <template x-if="!isDraft"><span class="text-sm text-gray-500" x-text="r.note || '—'"></span></template>
                        </td>
                    </tr>
                </template>
            </x-ui.table>
        </div>

        {{-- Konfirmasi finalisasi --}}
        <x-ui.modal title="Finalisasi Opname" size="sm" show="showFinalize">
            <p class="text-sm text-gray-600">
                Stok sistem akan disesuaikan ke jumlah hasil hitung untuk <span class="font-semibold" x-text="countedCount"></span> produk.
                Tindakan ini <b>tidak dapat dibatalkan</b>. Lanjutkan?
            </p>
            <x-slot:footer>
                <x-ui.button variant="outline" type="button" @click="showFinalize = false">Batal</x-ui.button>
                <x-ui.button type="button" icon="check" ::disabled="finalizing" @click="finalize()"><span x-text="finalizing ? 'Memproses...' : 'Ya, Finalisasi'"></span></x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    </div>
</x-layouts.app>
