<x-layouts.app title="Tambah Pelanggan" :breadcrumbs="['Pelanggan' => route('customers.index'), 'Tambah' => null]">
    @include('pages.customers._form', ['customerId' => null])
</x-layouts.app>
