<x-layouts.app title="Edit Kategori" :breadcrumbs="['Kategori' => route('categories.index'), 'Edit' => null]">
    @include('pages.categories._form', ['categoryId' => $id])
</x-layouts.app>
