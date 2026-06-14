{{-- Panel order servis. Membaca scope Alpine dari halaman create. --}}
<div class="flex h-full flex-col">
    {{-- Header: pelanggan, teknisi, tenggang waktu --}}
    <div class="space-y-3 border-b border-gray-200 p-4">
        <div class="flex items-center justify-between">
            <h2 class="flex items-center gap-2 text-base font-semibold text-gray-900">
                <x-heroicon-o-wrench class="h-5 w-5 text-primary-600" />
                Detail Servis
            </h2>
            <button type="button" x-show="cart.length" @click="clearCart()" class="text-xs font-medium text-danger-600 hover:underline">
                Kosongkan
            </button>
        </div>

        <div>
            <div class="mb-1 flex items-center justify-between">
                <label class="text-xs font-medium text-gray-500">Pelanggan</label>
                <button type="button" @click="openCustomerModal()" class="inline-flex items-center gap-1 text-xs font-medium text-primary-600 transition hover:text-primary-700">
                    <x-heroicon-o-plus class="h-3.5 w-3.5" />
                    Pelanggan Baru
                </button>
            </div>
            <select x-model="selectedCustomer" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                <option value="">Pelanggan Umum (Walk-in)</option>
                <template x-for="c in customers" :key="c.id">
                    <option :value="c.id" x-text="c.name"></option>
                </template>
            </select>
        </div>

        <div class="grid grid-cols-2 gap-2">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500">Teknisi</label>
                <select x-model="selectedTechnician" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                    <option value="">— Pilih —</option>
                    <template x-for="t in technicians" :key="t.id">
                        <option :value="t.id" x-text="t.name"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500">Tenggang Waktu</label>
                <input type="date" x-model="dueDate" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" />
            </div>
        </div>
    </div>

    {{-- Items --}}
    <div class="flex-1 overflow-y-auto p-4 scrollbar-thin">
        <div x-show="cart.length === 0" class="flex h-full flex-col items-center justify-center text-center text-gray-400">
            <x-heroicon-o-wrench-screwdriver class="h-12 w-12" />
            <p class="mt-2 text-sm">Belum ada item</p>
            <p class="text-xs">Pilih produk bahan atau tambah jasa</p>
        </div>

        <div class="space-y-3">
            <template x-for="(item, idx) in cart" :key="item.key">
                <div class="rounded-lg border border-gray-200 p-3">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-gray-800" x-text="item.name"></p>
                            <div class="mt-0.5 flex items-center gap-1.5">
                                <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-medium"
                                    :class="item.type === 'service' ? 'bg-warning-50 text-warning-700' : 'bg-gray-100 text-gray-500'"
                                    x-text="item.type === 'service' ? 'Jasa' : 'Bahan'"></span>
                                <span class="text-[11px] text-gray-400" x-text="'@ ' + rupiah(itemPrice(item))"></span>
                            </div>
                        </div>
                        <button type="button" @click="removeItem(idx)" class="rounded p-1 text-gray-300 transition hover:bg-danger-50 hover:text-danger-600">
                            <x-heroicon-o-x-mark class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="mt-2 flex items-center justify-between">
                        <template x-if="item.type === 'product'">
                            <div class="inline-flex items-center rounded-lg border border-gray-200">
                                <button type="button" @click="decQty(idx)" class="px-2 py-1 text-gray-500 transition hover:bg-gray-50">
                                    <x-heroicon-o-minus class="h-3.5 w-3.5" />
                                </button>
                                <input type="number" min="1" :max="item.stock" x-model.number="item.qty" @input="clampQty(idx)"
                                    class="w-10 border-0 p-0 text-center text-sm focus:ring-0" />
                                <button type="button" @click="incQty(idx)" :disabled="item.qty >= item.stock"
                                    class="px-2 py-1 text-gray-500 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40">
                                    <x-heroicon-o-plus class="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </template>
                        <template x-if="item.type === 'service'">
                            <span class="text-xs text-gray-400">Jasa</span>
                        </template>
                        <span class="text-sm font-semibold text-gray-800" x-text="rupiah(itemPrice(item) * item.qty)"></span>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Footer: jasa, diskon, pembayaran, simpan --}}
    <div class="border-t border-gray-200 p-4">
        <button type="button" @click="openServiceModal()" class="mb-3 flex w-full items-center justify-center gap-2 rounded-lg border border-dashed border-warning-300 py-2 text-sm font-medium text-warning-700 transition hover:bg-warning-50">
            <x-heroicon-o-wrench-screwdriver class="h-4 w-4" />
            Tambah Jasa
        </button>

        {{-- Diskon --}}
        <div class="mb-3">
            <div class="mb-1 flex items-center justify-between">
                <label class="text-xs font-medium text-gray-500">Diskon</label>
                <div class="inline-flex overflow-hidden rounded-lg border border-gray-200 text-xs font-medium">
                    <button type="button" @click="discountType = 'amount'" class="px-3 py-0.5 transition"
                        :class="discountType === 'amount' ? 'bg-primary-600 text-white' : 'bg-white text-gray-500 hover:bg-gray-50'">Rp</button>
                    <button type="button" @click="discountType = 'percent'" class="px-3 py-0.5 transition"
                        :class="discountType === 'percent' ? 'bg-primary-600 text-white' : 'bg-white text-gray-500 hover:bg-gray-50'">%</button>
                </div>
            </div>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400" x-text="discountType === 'percent' ? '%' : 'Rp'"></span>
                <input type="number" min="0" :max="discountType === 'percent' ? 100 : total" step="any" x-model="discountInput" placeholder="0"
                    class="block w-full rounded-lg border-gray-300 pl-10 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" />
            </div>
        </div>

        {{-- Ringkasan --}}
        <div class="mb-3 space-y-1">
            <div class="flex items-center justify-between text-sm text-gray-500">
                <span>Subtotal</span>
                <span x-text="rupiah(total)"></span>
            </div>
            <div x-show="discountAmount > 0" x-cloak class="flex items-center justify-between text-sm text-danger-600">
                <span>Diskon</span>
                <span x-text="'− ' + rupiah(discountAmount)"></span>
            </div>
            <div class="flex items-center justify-between border-t border-gray-100 pt-1 text-base">
                <span class="font-medium text-gray-600">Total</span>
                <span class="font-bold text-gray-900" x-text="rupiah(grandTotal)"></span>
            </div>
        </div>

        {{-- Status pembayaran --}}
        <div class="mb-3">
            <label class="mb-1 block text-xs font-medium text-gray-500">Status Pembayaran</label>
            <div class="grid grid-cols-3 gap-1 rounded-lg border border-gray-200 p-0.5 text-xs font-medium">
                <button type="button" @click="paymentChoice = 'belum_bayar'" class="rounded-md py-1.5 transition"
                    :class="paymentChoice === 'belum_bayar' ? 'bg-primary-600 text-white' : 'text-gray-500 hover:bg-gray-50'">Belum Bayar</button>
                <button type="button" @click="paymentChoice = 'dp'" class="rounded-md py-1.5 transition"
                    :class="paymentChoice === 'dp' ? 'bg-primary-600 text-white' : 'text-gray-500 hover:bg-gray-50'">DP</button>
                <button type="button" @click="paymentChoice = 'lunas'" class="rounded-md py-1.5 transition"
                    :class="paymentChoice === 'lunas' ? 'bg-primary-600 text-white' : 'text-gray-500 hover:bg-gray-50'">Lunas</button>
            </div>

            <div x-show="paymentChoice === 'dp'" x-cloak class="mt-2">
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">Rp</span>
                    <input type="number" min="0" :max="grandTotal" x-model.number="paidInput" placeholder="Jumlah DP"
                        class="block w-full rounded-lg border-gray-300 pl-10 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                </div>
                <p class="mt-1 flex items-center justify-between text-xs text-gray-500">
                    <span>Dibayar: <span class="font-medium text-gray-700" x-text="rupiah(paidAmount)"></span></span>
                    <span>Sisa: <span class="font-medium text-danger-600" x-text="rupiah(remaining)"></span></span>
                </p>
            </div>
        </div>

        <x-ui.button type="button" class="w-full" size="lg" icon="check" ::disabled="cart.length === 0 || processing" @click="save()">
            <span x-text="processing ? 'Menyimpan...' : 'Simpan & Mulai Servis'"></span>
        </x-ui.button>
    </div>
</div>
