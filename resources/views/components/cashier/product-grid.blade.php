{{-- Grid produk kasir. Membaca scope Alpine dari komponen induk (cashier). --}}
<div class="flex h-full flex-col">
    {{-- Search + filter kategori + toggle tampilan --}}
    <div class="mb-4 flex items-center gap-2">
        <div class="flex-1">
            <x-ui.search-input placeholder="Cari produk (nama / SKU)..." model="productSearch" />
        </div>

        {{-- Filter kategori --}}
        <select x-model="selectedCategory"
            class="flex-shrink-0 rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
            title="Filter kategori">
            <option value="">Semua Kategori</option>
            <template x-for="c in categories" :key="c.id">
                <option :value="c.id" x-text="c.name"></option>
            </template>
        </select>

        {{-- Switch mode card / list --}}
        <div class="inline-flex flex-shrink-0 rounded-lg border border-gray-200 p-0.5">
            <button type="button" @click="setView('card')"
                :class="viewMode === 'card' ? 'bg-primary-50 text-primary-600' : 'text-gray-400 hover:text-gray-600'"
                class="rounded-md p-1.5 transition" title="Tampilan kartu">
                <x-heroicon-o-squares-2x2 class="h-5 w-5" />
            </button>
            <button type="button" @click="setView('list')"
                :class="viewMode === 'list' ? 'bg-primary-50 text-primary-600' : 'text-gray-400 hover:text-gray-600'"
                class="rounded-md p-1.5 transition" title="Tampilan daftar">
                <x-heroicon-o-list-bullet class="h-5 w-5" />
            </button>
        </div>
    </div>

    {{-- Konten --}}
    <div class="-mr-1 flex-1 overflow-y-auto pr-1 scrollbar-thin">
        <div x-show="!loadingProducts && products.length === 0" class="flex h-40 items-center justify-center text-sm text-gray-400">
            Produk tidak ditemukan.
        </div>

        {{-- Mode kartu --}}
        <div x-show="viewMode === 'card'" class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
            <template x-for="p in products" :key="p.id">
                <button
                    type="button"
                    @click="addProduct(p)"
                    :disabled="p.stock <= 0 || !hasPrice(p, priceType)"
                    class="group flex flex-col rounded-xl border border-gray-200 bg-white p-3 text-left transition hover:border-primary-300 hover:shadow-md disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <div class="mb-2 flex aspect-square items-center justify-center overflow-hidden rounded-lg bg-gray-50 text-gray-300 group-hover:bg-primary-50 group-hover:text-primary-400">
                        <img x-show="p.image_url" :src="p.image_url" :alt="p.name" loading="lazy"
                            class="h-full w-full object-cover" x-on:error="p.image_url = null" />
                        <x-heroicon-o-cube x-show="!p.image_url" class="h-8 w-8" />
                    </div>
                    <span class="line-clamp-2 text-xs font-medium text-gray-800" x-text="p.name"></span>
                    <span class="mt-1 text-sm font-semibold text-primary-600" x-text="hasPrice(p, priceType) ? rupiah(p.prices[priceType]) : '—'"></span>
                    <span class="mt-0.5 text-[11px]" :class="p.stock <= p.min_stock ? 'text-danger-500' : 'text-gray-400'">
                        Stok: <span x-text="p.stock"></span>
                    </span>
                </button>
            </template>
        </div>

        {{-- Mode daftar --}}
        <div x-show="viewMode === 'list'" class="space-y-2">
            <template x-for="p in products" :key="p.id">
                <button
                    type="button"
                    @click="addProduct(p)"
                    :disabled="p.stock <= 0 || !hasPrice(p, priceType)"
                    class="group flex w-full items-center gap-3 rounded-lg border border-gray-200 bg-white p-2.5 text-left transition hover:border-primary-300 hover:bg-primary-50/40 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center overflow-hidden rounded-lg bg-gray-50 text-gray-300 group-hover:bg-primary-50 group-hover:text-primary-400">
                        <img x-show="p.image_url" :src="p.image_url" :alt="p.name" loading="lazy"
                            class="h-full w-full object-cover" x-on:error="p.image_url = null" />
                        <x-heroicon-o-cube x-show="!p.image_url" class="h-5 w-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-gray-800" x-text="p.name"></p>
                        <p class="text-[11px]" :class="p.stock <= p.min_stock ? 'text-danger-500' : 'text-gray-400'">
                            SKU: <span x-text="p.sku || '—'"></span> &middot; Stok: <span x-text="p.stock"></span>
                        </p>
                    </div>
                    <span class="flex-shrink-0 text-sm font-semibold text-primary-600" x-text="hasPrice(p, priceType) ? rupiah(p.prices[priceType]) : '—'"></span>
                    <x-heroicon-o-plus-circle class="h-5 w-5 flex-shrink-0 text-gray-300 transition group-hover:text-primary-500" />
                </button>
            </template>
        </div>
    </div>

    {{-- Pagination katalog (client-side) --}}
    <div x-show="catalogTotal > 0" class="mt-3 border-t border-gray-100 pt-3">
        <x-ui.pagination
            page="catalogPage"
            lastPage="catalogLastPage"
            total="catalogTotal"
            handler="goToCatalogPage"
        />
    </div>
</div>
