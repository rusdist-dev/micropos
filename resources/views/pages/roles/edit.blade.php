<x-layouts.app title="Edit Role" :breadcrumbs="['Role & Akses' => route('roles.index'), 'Edit' => null]">
    @include('pages.roles._form', ['roleId' => $id])
</x-layouts.app>
