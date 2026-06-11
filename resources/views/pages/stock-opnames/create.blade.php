<x-layouts.app title="Opname Baru" :breadcrumbs="['Stok Opname' => route('stock-opnames.index'), 'Baru' => null]">
    <div
        x-data="{
            saving: false, note: '',
            async submit() {
                this.saving = true;
                try {
                    const res = await window.api.post('/api/stock-opnames', { note: this.note });
                    window.flash.set('Sesi opname dibuat. Silakan masukkan hasil hitung.', 'success');
                    window.location.href = '{{ url('stock-opnames') }}/' + res.data.id;
                } catch (e) {
                    this.saving = false;
                    this.$store.toasts.error(e.message);
                }
            },
        }"
        class="max-w-2xl"
    >
        <form @submit.prevent="submit()" class="space-y-6">
            <x-ui.alert variant="info">
                Sesi opname akan mengambil snapshot stok sistem untuk seluruh produk aktif. Setelah dibuat, masukkan jumlah hasil hitung fisik, lalu finalisasi untuk menyinkronkan stok.
            </x-ui.alert>

            <x-ui.card title="Sesi Opname Baru">
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Catatan</label>
                    <textarea rows="3" x-model="note" placeholder="cth. Opname akhir bulan gudang utama (opsional)"
                        class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
                </div>
            </x-ui.card>

            <div class="flex items-center justify-end gap-2">
                <x-ui.button variant="outline" :href="route('stock-opnames.index')">Batal</x-ui.button>
                <x-ui.button type="submit" icon="clipboard-document-check" ::disabled="saving">
                    <span x-text="saving ? 'Membuat...' : 'Buat Sesi & Mulai Hitung'"></span>
                </x-ui.button>
            </div>
        </form>
    </div>
</x-layouts.app>
