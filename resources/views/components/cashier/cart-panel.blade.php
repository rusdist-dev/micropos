{{-- Panel keranjang kasir. Membaca scope Alpine dari komponen induk (cashier). --}}
<div class="flex h-full flex-col">
    {{-- Header + pelanggan --}}
    <div class="border-b border-gray-200 p-4">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="flex items-center gap-2 text-base font-semibold text-gray-900">
                <x-heroicon-o-shopping-cart class="h-5 w-5 text-primary-600" />
                Keranjang
            </h2>
            <button type="button" x-show="cart.length" @click="clearCart()" class="text-xs font-medium text-danger-600 hover:underline">
                Kosongkan
            </button>
        </div>

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

        {{-- Tipe harga: dipilih per transaksi, memengaruhi seluruh harga produk --}}
        <div class="mt-2">
            <label class="mb-1 block text-xs font-medium text-gray-500">Tipe Harga</label>
            <select x-model="priceType" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500">
                <template x-for="t in priceTypes" :key="t.code">
                    <option :value="t.code" x-text="t.name"></option>
                </template>
            </select>
        </div>
    </div>

    {{-- Items --}}
    <div class="flex-1 overflow-y-auto p-4 scrollbar-thin">
        <div x-show="cart.length === 0" class="flex h-full flex-col items-center justify-center text-center text-gray-400">
            <x-heroicon-o-shopping-cart class="h-12 w-12" />
            <p class="mt-2 text-sm">Keranjang kosong</p>
            <p class="text-xs">Pilih produk atau tambah jasa</p>
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
                                    x-text="item.type === 'service' ? 'Jasa' : 'Produk'"></span>
                                <span class="text-[11px] text-gray-400" x-text="'@ ' + rupiah(itemPrice(item))"></span>
                            </div>
                        </div>
                        <button type="button" @click="removeItem(idx)" class="rounded p-1 text-gray-300 transition hover:bg-danger-50 hover:text-danger-600">
                            <x-heroicon-o-x-mark class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="mt-2 flex items-center justify-between">
                        {{-- Qty stepper: hanya untuk produk. Jasa qty tetap 1. --}}
                        <template x-if="item.type === 'product'">
                            <div class="inline-flex items-center rounded-lg border border-gray-200">
                                <button type="button" @click="decQty(idx)" class="px-2 py-1 text-gray-500 transition hover:bg-gray-50">
                                    <x-heroicon-o-minus class="h-3.5 w-3.5" />
                                </button>
                                <input type="number" min="1" :max="item.stock"
                                    x-model.number="item.qty" @input="clampQty(idx)"
                                    class="w-10 border-0 p-0 text-center text-sm focus:ring-0" />
                                <button type="button" @click="incQty(idx)"
                                    :disabled="item.qty >= item.stock"
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

    {{-- Footer aksi --}}
    <div class="border-t border-gray-200 p-4">
        <button type="button" @click="openServiceModal()" class="mb-3 flex w-full items-center justify-center gap-2 rounded-lg border border-dashed border-warning-300 py-2 text-sm font-medium text-warning-700 transition hover:bg-warning-50">
            <x-heroicon-o-wrench-screwdriver class="h-4 w-4" />
            Tambah Jasa
        </button>

        <div class="mb-3 space-y-1">
            <div class="flex items-center justify-between text-sm text-gray-500">
                <span>Subtotal</span>
                <span x-text="rupiah(total)"></span>
            </div>
            <div x-show="discountAmount > 0" x-cloak class="flex items-center justify-between text-sm text-danger-600">
                <span>Diskon</span>
                <span x-text="'− ' + rupiah(discountAmount)"></span>
            </div>
            <div class="flex items-center justify-between border-t border-gray-100 pt-1 text-lg">
                <span class="font-medium text-gray-600">Total</span>
                <span class="font-bold text-gray-900" x-text="rupiah(grandTotal)"></span>
            </div>
        </div>

        <div class="mb-2 flex gap-2">
            <x-ui.button type="button" variant="outline" class="flex-1" icon="bookmark"
                ::disabled="cart.length === 0" @click="saveDraft()">
                Simpan Draft
            </x-ui.button>
            <x-ui.button type="button" variant="outline" class="flex-1" icon="inbox-stack" @click="showDrafts = true">
                Draft Tersimpan
                <span x-show="drafts.length" x-cloak class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-primary-600 px-1.5 text-xs font-semibold text-white" x-text="drafts.length"></span>
            </x-ui.button>
        </div>

        <x-ui.button type="button" class="w-full" size="lg" icon="banknotes"
            ::disabled="cart.length === 0" @click="openPaymentModal()">
            Bayar
        </x-ui.button>
    </div>
</div>
