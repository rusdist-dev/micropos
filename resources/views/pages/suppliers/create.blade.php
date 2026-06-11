<x-layouts.app title="Tambah Pemasok" :breadcrumbs="['Pemasok' => route('suppliers.index'), 'Tambah' => null]">
    @include('pages.suppliers._form', ['supplierId' => null])
</x-layouts.app>
