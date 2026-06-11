<x-layouts.app title="Tambah Tipe Harga" :breadcrumbs="['Tipe Harga' => route('price-types.index'), 'Tambah' => null]">
    @include('pages.price-types._form', ['priceTypeId' => null])
</x-layouts.app>
