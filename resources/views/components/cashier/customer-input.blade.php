{{-- Modal tambah pelanggan baru. Membaca scope Alpine induk (cashier). --}}
<x-ui.modal title="Tambah Pelanggan Baru" size="md" show="showCustomer">
    <div class="space-y-4">
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Nama Pelanggan <span class="text-danger-500">*</span></label>
            <input type="text" x-model="customerForm.name" placeholder="cth. PT Maju Jaya"
                @keydown.enter.prevent="addCustomer()"
                class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" />
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Telepon</label>
            <input type="text" x-model="customerForm.phone" placeholder="cth. 0812-3456-7890"
                class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500" />
        </div>
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Alamat</label>
            <textarea rows="2" x-model="customerForm.address" placeholder="Opsional"
                class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"></textarea>
        </div>
    </div>

    <x-slot:footer>
        <x-ui.button variant="outline" type="button" @click="showCustomer = false">Batal</x-ui.button>
        <x-ui.button type="button" icon="check" ::disabled="! customerForm.name || savingCustomer" @click="addCustomer()">
            <span x-text="savingCustomer ? 'Menyimpan...' : 'Simpan Pelanggan'"></span>
        </x-ui.button>
    </x-slot:footer>
</x-ui.modal>
