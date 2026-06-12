<x-layouts.app title="Konfigurasi" :breadcrumbs="['Konfigurasi' => null]">
    <form
        x-data="{
            loading: true,
            saving: false,
            errors: {},
            form: {
                store_name: '',
                store_address: '',
                store_phone: '',
                receipt_footer: '',
                primary_color: '{{ \App\Support\ColorPalette::DEFAULT_BASE }}',
            },
            logoFile: null,
            logoPreview: null,   // URL pratinjau (objectURL berkas baru atau URL tersimpan)
            currentLogo: null,   // URL logo tersimpan di server
            removeLogo: false,

            async init() {
                await this.load();
            },
            async load() {
                this.loading = true;
                try {
                    const res = await window.api.get('/api/settings');
                    const d = res.data;
                    this.form = {
                        store_name: d.store_name ?? '',
                        store_address: d.store_address ?? '',
                        store_phone: d.store_phone ?? '',
                        receipt_footer: d.receipt_footer ?? '',
                        primary_color: d.primary_color || '{{ \App\Support\ColorPalette::DEFAULT_BASE }}',
                    };
                    this.currentLogo = d.logo_url;
                    this.logoPreview = d.logo_url;
                } catch (e) {
                    this.$store.toasts.error('Gagal memuat konfigurasi: ' + e.message);
                } finally {
                    this.loading = false;
                }
            },

            onLogoChange(e) {
                const file = e.target.files[0];
                if (! file) return;
                this.logoFile = file;
                this.removeLogo = false;
                this.logoPreview = URL.createObjectURL(file);
            },
            clearLogo() {
                this.logoFile = null;
                this.logoPreview = null;
                this.removeLogo = true;
                this.$refs.logoInput.value = '';
            },

            // Pratinjau langsung warna pada elemen contoh (tanpa reload).
            get previewStyle() {
                return 'background-color:' + this.form.primary_color;
            },

            async submit() {
                this.saving = true;
                this.errors = {};
                const fd = new FormData();
                fd.append('store_name', this.form.store_name ?? '');
                fd.append('store_address', this.form.store_address ?? '');
                fd.append('store_phone', this.form.store_phone ?? '');
                fd.append('receipt_footer', this.form.receipt_footer ?? '');
                fd.append('primary_color', this.form.primary_color ?? '');
                if (this.logoFile) fd.append('logo', this.logoFile);
                if (this.removeLogo) fd.append('remove_logo', '1');
                try {
                    const res = await window.api.post('/api/settings', fd);
                    window.flash.set(res.message || 'Konfigurasi disimpan', 'success');
                    // Reload agar tema (CSS var), logo sidebar, & window.posSettings ikut terbarui.
                    window.location.reload();
                } catch (e) {
                    this.saving = false;
                    if (e.status === 422) { this.errors = e.errors; this.$store.toasts.error('Periksa kembali isian form.'); }
                    else this.$store.toasts.error(e.message);
                }
            },
            err(f) { return this.errors[f]?.[0] ?? null; },
        }"
        @submit.prevent="submit()"
        class="max-w-5xl"
    >
        <template x-if="loading"><div class="py-16"><x-ui.loading-spinner size="lg" label="Memuat konfigurasi..." /></div></template>

        <div x-show="!loading" x-cloak class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Kolom kiri: form --}}
            <div class="space-y-6 lg:col-span-2">
                <x-ui.card title="Identitas Toko">
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Nama Toko <span class="text-danger-500">*</span></label>
                            <input type="text" x-model="form.store_name" placeholder="cth. Toko Maju Jaya"
                                class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" :class="err('store_name') && 'border-danger-400'" />
                            <p x-show="err('store_name')" x-text="err('store_name')" class="mt-1 text-xs text-danger-600"></p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Alamat</label>
                            <textarea x-model="form.store_address" rows="2" placeholder="Alamat lengkap toko"
                                class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" :class="err('store_address') && 'border-danger-400'"></textarea>
                            <p x-show="err('store_address')" x-text="err('store_address')" class="mt-1 text-xs text-danger-600"></p>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium text-gray-700">Kontak</label>
                            <input type="text" x-model="form.store_phone" placeholder="cth. 0812-3456-7890"
                                class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" :class="err('store_phone') && 'border-danger-400'" />
                            <p x-show="err('store_phone')" x-text="err('store_phone')" class="mt-1 text-xs text-danger-600"></p>
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card title="Struk">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Kalimat Penutup Struk</label>
                        <textarea x-model="form.receipt_footer" rows="2" placeholder="cth. Terima kasih atas kunjungan Anda"
                            class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" :class="err('receipt_footer') && 'border-danger-400'"></textarea>
                        <p class="mt-1 text-xs text-gray-400">Dicetak di bagian bawah struk thermal &amp; invoice A4.</p>
                        <p x-show="err('receipt_footer')" x-text="err('receipt_footer')" class="mt-1 text-xs text-danger-600"></p>
                    </div>
                </x-ui.card>

                <x-ui.card title="Logo Toko">
                    <div class="flex items-start gap-5">
                        <div class="flex h-24 w-24 flex-shrink-0 items-center justify-center overflow-hidden rounded-xl border border-dashed border-gray-300 bg-gray-50">
                            <template x-if="logoPreview">
                                <img :src="logoPreview" alt="Logo" class="h-full w-full object-contain" />
                            </template>
                            <template x-if="!logoPreview">
                                <x-heroicon-o-photo class="h-8 w-8 text-gray-300" />
                            </template>
                        </div>
                        <div class="flex-1 space-y-2">
                            <input type="file" x-ref="logoInput" accept="image/png,image/jpeg,image/webp,image/svg+xml" @change="onLogoChange($event)"
                                class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-primary-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-primary-700 hover:file:bg-primary-100" />
                            <p class="text-xs text-gray-400">PNG, JPG, WEBP, atau SVG. Maks 2 MB.</p>
                            <button type="button" x-show="logoPreview" @click="clearLogo()"
                                class="inline-flex items-center gap-1 text-xs font-medium text-danger-600 hover:text-danger-700">
                                <x-heroicon-o-trash class="h-4 w-4" /> Hapus logo
                            </button>
                            <p x-show="err('logo')" x-text="err('logo')" class="text-xs text-danger-600"></p>
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card title="Tema Warna">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Warna Primary</label>
                        <div class="flex items-center gap-3">
                            <input type="color" x-model="form.primary_color"
                                class="h-10 w-14 cursor-pointer rounded-lg border border-gray-300 bg-white p-1" />
                            <input type="text" x-model="form.primary_color" maxlength="7" placeholder="#14b8a6"
                                class="w-32 rounded-lg border-gray-300 font-mono text-sm uppercase shadow-sm focus:border-primary-500 focus:ring-primary-500" :class="err('primary_color') && 'border-danger-400'" />
                            <div class="flex gap-1.5">
                                <template x-for="c in ['#14b8a6','#2563eb','#7c3aed','#db2777','#ea580c','#16a34a','#dc2626','#0891b2']" :key="c">
                                    <button type="button" @click="form.primary_color = c" :style="'background-color:'+c"
                                        class="h-7 w-7 rounded-full border border-gray-200 ring-offset-1 transition hover:scale-110"
                                        :class="form.primary_color.toLowerCase() === c && 'ring-2 ring-gray-400'"></button>
                                </template>
                            </div>
                        </div>
                        <p class="mt-1 text-xs text-gray-400">Berlaku ke seluruh aplikasi setelah disimpan.</p>
                        <p x-show="err('primary_color')" x-text="err('primary_color')" class="mt-1 text-xs text-danger-600"></p>
                    </div>
                </x-ui.card>
            </div>

            {{-- Kolom kanan: pratinjau --}}
            <div class="space-y-6">
                <x-ui.card title="Pratinjau">
                    <div class="space-y-4">
                        {{-- Pratinjau warna --}}
                        <div>
                            <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-400">Warna</p>
                            <div class="flex items-center gap-3">
                                <span class="h-10 w-10 rounded-lg shadow-inner" :style="previewStyle"></span>
                                <span class="inline-flex items-center rounded-lg px-4 py-2 text-sm font-medium text-white shadow-sm" :style="previewStyle">Tombol</span>
                            </div>
                        </div>

                        {{-- Pratinjau struk mini --}}
                        <div>
                            <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-400">Struk</p>
                            <div class="rounded-lg border border-gray-200 bg-white p-3 font-mono text-[11px] leading-relaxed text-gray-700">
                                <template x-if="logoPreview">
                                    <img :src="logoPreview" class="mx-auto mb-1 h-10 object-contain" alt="" />
                                </template>
                                <p class="text-center text-sm font-bold text-gray-900" x-text="form.store_name || 'Nama Toko'"></p>
                                <p class="text-center text-gray-500" x-show="form.store_address" x-text="form.store_address"></p>
                                <p class="text-center text-gray-500" x-show="form.store_phone" x-text="form.store_phone"></p>
                                <div class="my-2 border-t border-dashed border-gray-300"></div>
                                <div class="flex justify-between"><span>Contoh Produk</span><span>Rp 10.000</span></div>
                                <div class="flex justify-between font-bold"><span>TOTAL</span><span>Rp 10.000</span></div>
                                <div class="my-2 border-t border-dashed border-gray-300"></div>
                                <p class="text-center text-gray-500" x-text="form.receipt_footer || 'Terima kasih atas kunjungan Anda'"></p>
                            </div>
                        </div>
                    </div>
                </x-ui.card>

                <div class="flex items-center justify-end">
                    <x-ui.button type="submit" icon="check" ::disabled="saving || !form.store_name">
                        <span x-text="saving ? 'Menyimpan...' : 'Simpan Konfigurasi'"></span>
                    </x-ui.button>
                </div>
            </div>
        </div>
    </form>
</x-layouts.app>
