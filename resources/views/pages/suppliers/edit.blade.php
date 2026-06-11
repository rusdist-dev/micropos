<x-layouts.app title="Edit Pemasok" :breadcrumbs="['Pemasok' => route('suppliers.index'), 'Edit' => null]">
    @include('pages.suppliers._form', ['supplierId' => $id])
</x-layouts.app>
