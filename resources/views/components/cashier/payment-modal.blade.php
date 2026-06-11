{{-- Modal pembayaran. Membaca scope Alpine induk (cashier). --}}
<x-ui.modal title="Pembayaran" size="md" show="showPayment">
    <div class="space-y-5">
        {{-- Total --}}
        <div class="rounded-lg bg-gray-50 p-4 text-center">
            <p class="text-sm text-gray-500">Total Tagihan</p>
            <p class="mt-1 text-3xl font-bold text-gray-900" x-text="rupiah(total)"></p>
        </div>

        {{-- Input bayar --}}
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Jumlah Bayar</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">Rp</span>
                <input type="number" min="0" x-model.number="paymentAmount" x-ref="payInput"
                    class="block w-full rounded-lg border-gray-300 pl-10 text-lg font-semibold shadow-sm focus:border-primary-500 focus:ring-primary-500" />
            </div>

            {{-- Nominal cepat --}}
            <div class="mt-2 grid grid-cols-4 gap-2">
                <template x-for="nom in quickAmounts" :key="nom">
                    <button type="button" @click="paymentAmount = nom"
                        class="rounded-md border border-gray-200 py-1.5 text-xs font-medium text-gray-600 transition hover:border-primary-300 hover:bg-primary-50 hover:text-primary-700"
                        x-text="'Rp ' + nom.toLocaleString('id-ID')"></button>
                </template>
                <button type="button" @click="paymentAmount = total"
                    class="rounded-md border border-primary-200 bg-primary-50 py-1.5 text-xs font-medium text-primary-700 transition hover:bg-primary-100">
                    Uang Pas
                </button>
            </div>
        </div>

        {{-- Kembalian --}}
        <div class="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3">
            <span class="text-sm font-medium text-gray-600">Kembalian</span>
            <span class="text-xl font-bold"
                :class="change >= 0 ? 'text-primary-600' : 'text-danger-600'"
                x-text="rupiah(change)"></span>
        </div>

        <p x-show="paymentAmount !== '' && change < 0" x-cloak class="text-center text-sm text-danger-600">
            Jumlah bayar kurang dari total.
        </p>
    </div>

    <x-slot:footer>
        <x-ui.button variant="outline" type="button" @click="showPayment = false">Batal</x-ui.button>
        <x-ui.button type="button" icon="check" ::disabled="paymentAmount === '' || change < 0 || processing"
            @click="checkout()">
            <span x-text="processing ? 'Memproses...' : 'Selesaikan Transaksi'"></span>
        </x-ui.button>
    </x-slot:footer>
</x-ui.modal>
