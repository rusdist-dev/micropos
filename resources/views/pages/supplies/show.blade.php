<x-layouts.app title="Detail Supply" :breadcrumbs="['Supply Barang' => route('supplies.index'), 'Detail' => null]">
    <div
        x-data="{
            id: {{ (int) $id }}, loading: true, error: null, supply: null,
            fmtDate(iso) { return iso ? new Date(iso).toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'short' }) : '—'; },
            async load() {
                this.loading = true; this.error = null;
                try { const res = await window.api.get('/api/supplies/' + this.id); this.supply = res.data; }
                catch (e) { this.error = e.message; } finally { this.loading = false; }
            },
        }"
        x-init="load()"
    >
        <div x-show="loading" class="py-10"><x-ui.loading-spinner size="lg" label="Memuat..." /></div>
        <template x-if="error"><x-ui.alert variant="danger" title="Gagal memuat"><span x-text="error"></span></x-ui.alert></template>

        <div x-show="!loading && supply" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <x-ui.card title="Item Supply">
                    <x-slot:actions><span class="inline-flex items-center rounded-full bg-primary-50 px-2.5 py-0.5 text-xs font-medium text-primary-700 ring-1 ring-inset ring-primary-600/20" x-text="supply?.code"></span></x-slot:actions>
                    <x-ui.table>
                        <x-slot:head>
                            <x-ui.th>Produk</x-ui.th>
                            <x-ui.th align="center">Qty</x-ui.th>
                            <x-ui.th align="right">Modal</x-ui.th>
                            <x-ui.th align="right">Subtotal</x-ui.th>
                        </x-slot:head>
                        <template x-for="it in (supply?.items ?? [])" :key="it.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-1">
                                    <p class="text-sm font-medium text-gray-800" x-text="it.product_name"></p>
                                    <template x-if="it.prices && it.prices.length">
                                        <p class="mt-0.5 text-[11px] text-gray-400">Harga jual diperbarui: <span x-text="it.prices.map(p => p.price_type + '=' + window.rupiah(p.price)).join(', ')"></span></p>
                                    </template>
                                </td>
                                <td class="px-4 py-1 text-center text-sm text-gray-700" x-text="'+' + it.qty"></td>
                                <td class="px-4 py-1 text-right text-sm text-gray-700" x-text="window.rupiah(it.purchase_price ?? 0)"></td>
                                <td class="px-4 py-1 text-right text-sm font-medium text-gray-800" x-text="window.rupiah(it.line_cost)"></td>
                            </tr>
                        </template>
                    </x-ui.table>
                </x-ui.card>
            </div>
            <div class="space-y-6">
                <x-ui.card title="Informasi">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Tanggal</dt><dd class="font-medium text-gray-800" x-text="fmtDate(supply?.created_at)"></dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Pemasok</dt><dd class="font-medium text-gray-800" x-text="supply?.supplier_name"></dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Petugas</dt><dd class="font-medium text-gray-800" x-text="supply?.user_name"></dd></div>
                        <div class="flex justify-between border-t border-gray-100 pt-3"><dt class="text-gray-500">Total Modal</dt><dd class="font-semibold text-gray-900" x-text="window.rupiah(supply?.total_cost)"></dd></div>
                        <template x-if="supply?.note"><div class="border-t border-gray-100 pt-3"><dt class="mb-1 text-gray-500">Catatan</dt><dd class="text-gray-700" x-text="supply?.note"></dd></div></template>
                    </dl>
                </x-ui.card>
            </div>
        </div>
    </div>
</x-layouts.app>
