{{--
    Form produk (create & edit) terhubung API.
    Variabel: $productId (null untuk create, id untuk edit).
--}}
@php $productId = $productId ?? null; @endphp

<form
    x-data="{
        productId: {{ $productId ? (int) $productId : 'null' }},
        loading: {{ $productId ? 'true' : 'false' }},
        saving: false,
        errors: {},
        priceTypes: [],
        categories: [],
        prices: {},
        defaultType: '',
        imageFile: null,
        imagePreview: null,
        form: { name: '', sku: '', category_id: '', brand: '', stock: 0, min_stock: 0, purchase_price: 0, description: '', is_active: true },

        async init() {
            await Promise.all([this.loadPriceTypes(), this.loadCategories()]);
            if (this.productId) await this.loadProduct();
        },
        async loadPriceTypes() {
            try {
                const res = await window.api.get('/api/price-types?all=1');
                this.priceTypes = res.data;
                for (const t of this.priceTypes) {
                    if (this.prices[t.code] === undefined) this.prices[t.code] = '';
                }
                if (! this.defaultType && this.priceTypes.length) this.defaultType = this.priceTypes[0].code;
            } catch (e) { this.$store.toasts.error('Gagal memuat tipe harga: ' + e.message); }
        },
        async loadCategories() {
            try {
                const res = await window.api.get('/api/categories?all=1');
                this.categories = res.data;
            } catch (e) { this.$store.toasts.error('Gagal memuat kategori: ' + e.message); }
        },
        async loadProduct() {
            this.loading = true;
            try {
                const res = await window.api.get('/api/products/' + this.productId);
                const p = res.data;
                this.form = {
                    name: p.name, sku: p.sku ?? '', category_id: p.category_id ?? '', brand: p.brand ?? '',
                    stock: p.stock, min_stock: p.min_stock, purchase_price: p.purchase_price,
                    description: p.description ?? '', is_active: p.is_active,
                };
                this.imagePreview = p.image_url;
                for (const t of this.priceTypes) this.prices[t.code] = '';
                for (const row of p.prices) {
                    this.prices[row.price_type] = row.price;
                    if (row.is_active_default) this.defaultType = row.price_type;
                }
            } catch (e) {
                this.$store.toasts.error('Gagal memuat produk: ' + e.message);
            } finally {
                this.loading = false;
            }
        },
        onImage(e) {
            const file = e.target.files[0];
            if (! file) return;
            this.imageFile = file;
            this.imagePreview = URL.createObjectURL(file);
        },
        filledTypes() {
            return this.priceTypes.filter((t) => this.prices[t.code] !== '' && this.prices[t.code] !== null && this.prices[t.code] !== undefined);
        },
        async submit() {
            this.errors = {};
            const filled = this.filledTypes();
            if (filled.length === 0) {
                this.$store.toasts.error('Isi minimal satu harga.');
                return;
            }
            // Pastikan default termasuk tipe yang terisi.
            if (! filled.find((t) => t.code === this.defaultType)) {
                this.defaultType = filled[0].code;
            }

            this.saving = true;
            const fd = new FormData();
            fd.append('name', this.form.name ?? '');
            if (this.form.sku) fd.append('sku', this.form.sku);
            // Selalu kirim category_id (kosong = hapus kategori; '' -> null di server).
            fd.append('category_id', this.form.category_id ?? '');
            if (this.form.brand) fd.append('brand', this.form.brand);
            fd.append('stock', this.form.stock ?? 0);
            fd.append('min_stock', this.form.min_stock ?? 0);
            fd.append('purchase_price', this.form.purchase_price ?? 0);
            if (this.form.description) fd.append('description', this.form.description);
            fd.append('is_active', this.form.is_active ? '1' : '0');
            filled.forEach((t, i) => {
                fd.append('prices[' + i + '][price_type]', t.code);
                fd.append('prices[' + i + '][price]', this.prices[t.code]);
            });
            fd.append('default_type', this.defaultType);
            if (this.imageFile) fd.append('image', this.imageFile);

            const url = this.productId ? '/api/products/' + this.productId : '/api/products';
            if (this.productId) fd.append('_method', 'PUT');

            try {
                const res = await window.api.post(url, fd);
                window.flash.set(res.message || 'Produk disimpan', 'success');
                window.location.href = '{{ route('products.index') }}';
            } catch (e) {
                this.saving = false;
                if (e.status === 422) {
                    this.errors = e.errors;
                    this.$store.toasts.error('Periksa kembali isian form.');
                } else {
                    this.$store.toasts.error(e.message);
                }
            }
        },
        err(field) { return this.errors[field]?.[0] ?? null; },
    }"
    @submit.prevent="submit()"
    class="space-y-6"
>
    <template x-if="loading">
        <div class="py-10"><x-ui.loading-spinner size="lg" label="Memuat data produk..." /></div>
    </template>

    <div x-show="!loading" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Kolom kiri --}}
        <div class="space-y-6 lg:col-span-2">
            <x-ui.card title="Informasi Produk">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700">Nama Produk <span class="text-danger-500">*</span></label>
                        <input type="text" x-model="form.name" placeholder="cth. Kabel UTP Cat6"
                            class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" :class="err('name') && 'border-danger-400'" />
                        <p x-show="err('name')" x-text="err('name')" class="mt-1 text-xs text-danger-600"></p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">SKU</label>
                        <input type="text" x-model="form.sku" placeholder="cth. KBL-UTP6"
                            class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" :class="err('sku') && 'border-danger-400'" />
                        <p x-show="err('sku')" x-text="err('sku')" class="mt-1 text-xs text-danger-600"></p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Kategori</label>
                        <select x-model="form.category_id"
                            class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" :class="err('category_id') && 'border-danger-400'">
                            <option value="">Tanpa Kategori</option>
                            <template x-for="c in categories" :key="c.id">
                                <option :value="c.id" x-text="c.name"></option>
                            </template>
                        </select>
                        <p x-show="err('category_id')" x-text="err('category_id')" class="mt-1 text-xs text-danger-600"></p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Merek</label>
                        <input type="text" x-model="form.brand" placeholder="cth. Belden"
                            class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Stok <span class="text-danger-500">*</span></label>
                        <input type="number" min="0" x-model.number="form.stock"
                            class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" :class="err('stock') && 'border-danger-400'" />
                        <p x-show="err('stock')" x-text="err('stock')" class="mt-1 text-xs text-danger-600"></p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Min. Stok</label>
                        <input type="number" min="0" x-model.number="form.min_stock"
                            class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700">Harga Beli (Modal)</label>
                        <input type="number" min="0" x-model.number="form.purchase_price"
                            class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-gray-700">Deskripsi</label>
                        <textarea rows="3" x-model="form.description" placeholder="Deskripsi produk (opsional)"
                            class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
                    </div>
                </div>
            </x-ui.card>

            {{-- Multi-harga dinamis --}}
            <x-ui.card title="Harga Jual per Tipe">
                <p class="mb-4 text-sm text-gray-500">Isi harga untuk tipe yang berlaku (boleh sebagian). Pilih satu sebagai harga default kasir.</p>
                <p x-show="err('prices') || err('default_type')" x-text="err('prices') || err('default_type')" class="mb-3 text-xs text-danger-600"></p>

                <div class="space-y-3">
                    <template x-for="t in priceTypes" :key="t.code">
                        <div class="flex items-center gap-4 rounded-lg border border-gray-200 p-3">
                            <label class="flex flex-1 items-center gap-3">
                                <input type="radio" :value="t.code" x-model="defaultType" class="text-primary-600 focus:ring-primary-500" />
                                <span class="w-24 text-sm font-medium text-gray-700" x-text="t.name"></span>
                            </label>
                            <div class="relative flex-1">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-gray-400">Rp</span>
                                <input type="number" min="0" x-model.number="prices[t.code]"
                                    class="block w-full rounded-lg border-gray-300 pl-9 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" />
                            </div>
                            <span x-show="defaultType === t.code"><x-ui.badge variant="primary" size="sm">Default</x-ui.badge></span>
                        </div>
                    </template>
                </div>
            </x-ui.card>
        </div>

        {{-- Kolom kanan --}}
        <div class="space-y-6">
            <x-ui.card title="Gambar Produk">
                <div class="flex aspect-square w-full items-center justify-center overflow-hidden rounded-lg border-2 border-dashed border-gray-200 bg-gray-50 text-gray-300">
                    <template x-if="imagePreview"><img :src="imagePreview" class="h-full w-full object-cover" /></template>
                    <template x-if="!imagePreview">
                        <div class="text-center"><x-heroicon-o-photo class="mx-auto h-10 w-10" /><p class="mt-2 text-xs text-gray-400">Belum ada gambar</p></div>
                    </template>
                </div>
                <div class="mt-3">
                    <input type="file" accept="image/*" @change="onImage($event)"
                        class="block w-full text-xs text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-primary-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-primary-700 hover:file:bg-primary-100" />
                    <p x-show="err('image')" x-text="err('image')" class="mt-1 text-xs text-danger-600"></p>
                </div>
            </x-ui.card>

            <x-ui.card title="Status">
                <label class="flex items-center gap-3">
                    <input type="checkbox" x-model="form.is_active" class="rounded text-primary-600 focus:ring-primary-500" />
                    <span class="text-sm text-gray-700">Produk aktif (tampil di kasir)</span>
                </label>
            </x-ui.card>
        </div>
    </div>

    <div x-show="!loading" class="flex items-center justify-end gap-2">
        <x-ui.button variant="outline" :href="route('products.index')">Batal</x-ui.button>
        <x-ui.button type="submit" icon="check" ::disabled="saving">
            <span x-text="saving ? 'Menyimpan...' : 'Simpan Produk'"></span>
        </x-ui.button>
    </div>
</form>
