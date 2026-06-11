<x-layouts.app title="Tambah Produk" :breadcrumbs="['Produk' => route('products.index'), 'Tambah' => null]">
    @include('pages.products._form', ['productId' => null])
</x-layouts.app>
