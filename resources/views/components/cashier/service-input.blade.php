{{-- Modal input jasa custom. Membaca scope Alpine induk (cashier). --}}
<x-ui.modal title="Tambah Jasa" size="md" show="showService">
    <div class="space-y-4">
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Nama Jasa <span class="text-danger-500">*</span></label>
            <input type="text" x-model="serviceForm.name" placeholder="cth. Instalasi Jaringan LAN"
                class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" />
            {{-- Saran dari template jasa --}}
            <div class="mt-2 flex flex-wrap gap-1.5">
                <template x-for="s in services" :key="s.id">
                    <button type="button" @click="serviceForm.name = s.name; serviceForm.price = s.default_price"
                        class="rounded-full bg-gray-100 px-2.5 py-1 text-xs text-gray-600 transition hover:bg-primary-50 hover:text-primary-700"
                        x-text="s.name"></button>
                </template>
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Harga <span class="text-danger-500">*</span></label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-gray-400">Rp</span>
                <input type="number" min="0" x-model.number="serviceForm.price"
                    class="block w-full rounded-lg border-gray-300 pl-9 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" />
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Catatan</label>
            <textarea rows="2" x-model="serviceForm.note" placeholder="Opsional"
                class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
        </div>
    </div>

    <x-slot:footer>
        <x-ui.button variant="outline" type="button" @click="showService = false">Batal</x-ui.button>
        <x-ui.button type="button" icon="plus" @click="addService()"
            ::disabled="! serviceForm.name || serviceForm.price === ''">Tambah ke Keranjang</x-ui.button>
    </x-slot:footer>
</x-ui.modal>
