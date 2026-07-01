<x-layouts.app title="Detail Transaksi" :breadcrumbs="['Riwayat' => route('transactions.index'), 'Detail' => null]">
    <div
        x-data="{
            id: {{ (int) $id }},
            loading: true,
            error: null,
            trx: null,
            fmtDate(iso) { return iso ? new Date(iso).toLocaleString('id-ID', { dateStyle: 'long', timeStyle: 'short' }) : '—'; },
            async load() {
                this.loading = true; this.error = null;
                try {
                    const res = await window.api.get('/api/transactions/' + this.id);
                    this.trx = res.data;
                } catch (e) { this.error = e.message; } finally { this.loading = false; }
            },
        }"
        x-init="load()"
    >
        <div x-show="loading" class="py-10"><x-ui.loading-spinner size="lg" label="Memuat transaksi..." /></div>
        <template x-if="error"><x-ui.alert variant="danger" title="Gagal memuat"><span x-text="error"></span></x-ui.alert></template>

        <div x-show="!loading && trx" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Items --}}
            <div class="space-y-6 lg:col-span-2">
                <x-ui.card title="Item Transaksi">
                    <x-slot:actions>
                        <span class="inline-flex items-center rounded-full bg-primary-50 px-2.5 py-0.5 text-xs font-medium text-primary-700 ring-1 ring-inset ring-primary-600/20" x-text="trx?.invoice_number"></span>
                    </x-slot:actions>

                    <x-ui.table>
                        <x-slot:head>
                            <x-ui.th>Item</x-ui.th>
                            <x-ui.th>Tipe Harga</x-ui.th>
                            <x-ui.th align="right">Harga</x-ui.th>
                            <x-ui.th align="center">Qty</x-ui.th>
                            <x-ui.th align="right">Subtotal</x-ui.th>
                        </x-slot:head>
                        <template x-for="item in (trx?.items ?? [])" :key="item.id">
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-gray-800" x-text="item.item_name"></span>
                                        <template x-if="item.item_type === 'service'">
                                            <span class="inline-flex items-center rounded-full bg-warning-50 px-2 py-0.5 text-[10px] font-medium text-warning-700 ring-1 ring-inset ring-warning-600/20">Jasa</span>
                                        </template>
                                    </div>
                                </td>
                                <td class="px-4 py-1 text-sm capitalize text-gray-500" x-text="item.price_type_used || '—'"></td>
                                <td class="px-4 py-1 text-right text-sm text-gray-700" x-text="window.rupiah(item.price_snapshot)"></td>
                                <td class="px-4 py-1 text-center text-sm text-gray-700" x-text="item.qty"></td>
                                <td class="px-4 py-1 text-right text-sm font-medium text-gray-800" x-text="window.rupiah(item.subtotal)"></td>
                            </tr>
                        </template>
                    </x-ui.table>
                </x-ui.card>
            </div>

            {{-- Ringkasan --}}
            <div class="space-y-6">
                <x-ui.card title="Informasi">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Tanggal</dt><dd class="font-medium text-gray-800" x-text="fmtDate(trx?.created_at)"></dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Pelanggan</dt><dd class="font-medium text-gray-800" x-text="trx?.customer_name || 'Pelanggan Umum'"></dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Kasir</dt><dd class="font-medium text-gray-800" x-text="trx?.kasir_name"></dd></div>
                        <template x-if="trx?.note">
                            <div class="border-t border-gray-100 pt-3"><dt class="mb-1 text-gray-500">Catatan</dt><dd class="text-gray-700" x-text="trx?.note"></dd></div>
                        </template>
                    </dl>
                </x-ui.card>

                <x-ui.card title="Pembayaran">
                    <dl class="space-y-3 text-sm">
                        <template x-if="trx?.discount > 0">
                            <div class="space-y-3">
                                <div class="flex justify-between"><dt class="text-gray-500">Subtotal</dt><dd class="text-gray-800" x-text="window.rupiah(trx?.subtotal)"></dd></div>
                                <div class="flex justify-between"><dt class="text-gray-500">Diskon</dt><dd class="text-danger-600" x-text="'− ' + window.rupiah(trx?.discount)"></dd></div>
                            </div>
                        </template>
                        <div class="flex justify-between"><dt class="text-gray-500">Total</dt><dd class="font-semibold text-gray-900" x-text="window.rupiah(trx?.total)"></dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Bayar</dt><dd class="text-gray-800" x-text="window.rupiah(trx?.payment_amount)"></dd></div>
                        <div class="flex justify-between border-t border-gray-100 pt-3"><dt class="text-gray-500">Kembalian</dt><dd class="font-semibold text-primary-600" x-text="window.rupiah(trx?.change_amount)"></dd></div>
                    </dl>
                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <x-ui.button variant="outline" type="button" icon="printer" @click="$store.receipt.print(trx, 'thermal')">Struk 58mm</x-ui.button>
                        <x-ui.button type="button" icon="document-text" @click="$store.receipt.print(trx, 'a4')">Cetak A4</x-ui.button>
                    </div>
                </x-ui.card>
            </div>
        </div>
    </div>
</x-layouts.app>
