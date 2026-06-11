<x-layouts.app title="Edit Pelanggan" :breadcrumbs="['Pelanggan' => route('customers.index'), 'Edit' => null]">
    @include('pages.customers._form', ['customerId' => $id])
</x-layouts.app>
