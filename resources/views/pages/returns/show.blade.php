<x-layouts.app title="Detail Retur" :breadcrumbs="['Retur Barang' => route('returns.index'), 'Detail' => null]">
    <div
        x-data="{
            id: {{ (int) $id }}, loading: true, error: null, ret: null,
            fmtDate(iso) { return iso ? new Date(iso).toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'short' }) : '—'; },
            returnedItems() { return (this.ret?.items ?? []).filter(i => i.direction === 'returned'); },
            exchangeItems() { return (this.ret?.items ?? []).filter(i => i.direction === 'exchange'); },
            async load() {
                this.loading = true; this.error = null;
                try { const res = await window.api.get('/api/returns/' + this.id); this.ret = res.data; }
                catch (e) { this.error = e.message; } finally { this.loading = false; }
            },
        }"
        x-init="load()"
    >
        <div x-show="loading" class="py-10"><x-ui.loading-spinner size="lg" label="Memuat..." /></div>
        <template x-if="error"><x-ui.alert variant="danger" title="Gagal memuat"><span x-text="error"></span></x-ui.alert></template>

        <div x-show="!loading && ret" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <x-ui.card title="Item Dikembalikan">
                    <x-slot:actions><span class="inline-flex items-center rounded-full bg-primary-50 px-2.5 py-0.5 text-xs font-medium text-primary-700 ring-1 ring-inset ring-primary-600/20" x-text="ret?.code"></span></x-slot:actions>
                    <div x-show="returnedItems().length === 0" class="py-2 text-sm text-gray-400">Tidak ada.</div>
                    <x-ui.table x-show="returnedItems().length > 0">
                        <x-slot:head>
                            <x-ui.th>Produk</x-ui.th>
                            <x-ui.th align="right">Harga</x-ui.th>
                            <x-ui.th align="center">Qty</x-ui.th>
                            <x-ui.th align="center">Restok</x-ui.th>
                            <x-ui.th align="right">Subtotal</x-ui.th>
                        </x-slot:head>
                        <template x-for="it in returnedItems()" :key="it.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-1 text-sm font-medium text-gray-800" x-text="it.item_name"></td>
                                <td class="px-4 py-1 text-right text-sm text-gray-700" x-text="window.rupiah(it.price_snapshot)"></td>
                                <td class="px-4 py-1 text-center text-sm text-gray-700" x-text="it.qty"></td>
                                <td class="px-4 py-1 text-center">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium ring-1 ring-inset"
                                        :class="it.restock ? 'bg-primary-50 text-primary-700 ring-primary-600/20' : 'bg-danger-50 text-danger-700 ring-danger-600/20'"
                                        x-text="it.restock ? 'Ya' : 'Rusak'"></span>
                                </td>
                                <td class="px-4 py-1 text-right text-sm font-medium text-gray-800" x-text="window.rupiah(it.subtotal)"></td>
                            </tr>
                        </template>
                    </x-ui.table>
                </x-ui.card>

                <x-ui.card title="Item Penukaran" x-show="exchangeItems().length > 0">
                    <x-ui.table>
                        <x-slot:head>
                            <x-ui.th>Produk</x-ui.th>
                            <x-ui.th>Tipe Harga</x-ui.th>
                            <x-ui.th align="right">Harga</x-ui.th>
                            <x-ui.th align="center">Qty</x-ui.th>
                            <x-ui.th align="right">Subtotal</x-ui.th>
                        </x-slot:head>
                        <template x-for="it in exchangeItems()" :key="it.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-1 text-sm font-medium text-gray-800" x-text="it.item_name"></td>
                                <td class="px-4 py-1 text-sm capitalize text-gray-500" x-text="it.price_type_used || '—'"></td>
                                <td class="px-4 py-1 text-right text-sm text-gray-700" x-text="window.rupiah(it.price_snapshot)"></td>
                                <td class="px-4 py-1 text-center text-sm text-gray-700" x-text="it.qty"></td>
                                <td class="px-4 py-1 text-right text-sm font-medium text-gray-800" x-text="window.rupiah(it.subtotal)"></td>
                            </tr>
                        </template>
                    </x-ui.table>
                </x-ui.card>
            </div>

            <div class="space-y-6">
                <x-ui.card title="Informasi">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Invoice Asal</dt><dd class="font-medium text-primary-700" x-text="ret?.invoice_number"></dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Tanggal</dt><dd class="font-medium text-gray-800" x-text="fmtDate(ret?.created_at)"></dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Kasir</dt><dd class="font-medium text-gray-800" x-text="ret?.kasir_name"></dd></div>
                        <template x-if="ret?.note"><div class="border-t border-gray-100 pt-3"><dt class="mb-1 text-gray-500">Catatan</dt><dd class="text-gray-700" x-text="ret?.note"></dd></div></template>
                    </dl>
                </x-ui.card>

                <x-ui.card title="Saldo">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Nilai Dikembalikan</dt><dd class="text-gray-800" x-text="window.rupiah(ret?.returned_total)"></dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Nilai Penukaran</dt><dd class="text-gray-800" x-text="window.rupiah(ret?.exchange_total)"></dd></div>
                        <div class="flex justify-between border-t border-gray-100 pt-3"><dt class="text-gray-500">Dibayar Pelanggan</dt><dd class="text-gray-800" x-text="window.rupiah(ret?.payment_amount)"></dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Dikembalikan</dt><dd class="font-semibold text-danger-600" x-text="window.rupiah(ret?.refund_amount)"></dd></div>
                    </dl>
                </x-ui.card>
            </div>
        </div>
    </div>
</x-layouts.app>
