<x-layouts.app title="Tambah Kategori" :breadcrumbs="['Kategori' => route('categories.index'), 'Tambah' => null]">
    @include('pages.categories._form', ['categoryId' => null])
</x-layouts.app>
