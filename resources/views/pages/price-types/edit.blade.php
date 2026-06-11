<x-layouts.app title="Edit Tipe Harga" :breadcrumbs="['Tipe Harga' => route('price-types.index'), 'Edit' => null]">
    @include('pages.price-types._form', ['priceTypeId' => $id])
</x-layouts.app>
