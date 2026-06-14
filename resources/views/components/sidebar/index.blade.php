@php
    // Definisi menu sidebar. Setiap item menyaring berdasarkan permission user aktif.
    $menu = [
        'UTAMA' => [
            ['label' => 'Dashboard', 'icon' => 'home',                'route' => 'dashboard',          'active' => 'dashboard',     'permission' => 'dashboard.view'],
        ],
        'DATA' => [
            ['label' => 'Produk',     'icon' => 'cube',                'route' => 'products.index',     'active' => 'products.*',     'permission' => 'products.view'],
            ['label' => 'Kategori',   'icon' => 'rectangle-stack',     'route' => 'categories.index',   'active' => 'categories.*',   'permission' => 'categories.view'],
            ['label' => 'Tipe Harga', 'icon' => 'tag',                 'route' => 'price-types.index',  'active' => 'price-types.*',  'permission' => 'price-types.view'],
            ['label' => 'Pelanggan',  'icon' => 'users',               'route' => 'customers.index',    'active' => 'customers.*',    'permission' => 'customers.view'],
            ['label' => 'Pemasok',    'icon' => 'truck',               'route' => 'suppliers.index',    'active' => 'suppliers.*',    'permission' => 'suppliers.view'],
            ['label' => 'Jasa',       'icon' => 'wrench-screwdriver',  'route' => 'services.index',     'active' => 'services.*',     'permission' => 'services.view'],
            ['label' => 'Teknisi',    'icon' => 'identification',      'route' => 'technicians.index',  'active' => 'technicians.*',  'permission' => 'technicians.view'],
        ],
        'INVENTORI' => [
            ['label' => 'Stok Opname',   'icon' => 'clipboard-document-check', 'route' => 'stock-opnames.index', 'active' => 'stock-opnames.*', 'permission' => 'stock-opnames.view'],
            ['label' => 'Supply Barang', 'icon' => 'inbox-arrow-down',         'route' => 'supplies.index',      'active' => 'supplies.*',      'permission' => 'supplies.view'],
        ],
        'TRANSAKSI' => [
            ['label' => 'Kasir',     'icon' => 'shopping-cart',       'route' => 'cashier.index',      'active' => 'cashier.*',     'permission' => 'transactions.create'],
            ['label' => 'Order Servis', 'icon' => 'wrench',           'route' => 'service-orders.index', 'active' => 'service-orders.*', 'permission' => 'service-orders.view'],
            ['label' => 'Riwayat',   'icon' => 'clock',               'route' => 'transactions.index', 'active' => 'transactions.*','permission' => 'transactions.view'],
            ['label' => 'Retur',     'icon' => 'arrow-uturn-left',    'route' => 'returns.index',      'active' => 'returns.*',     'permission' => 'returns.view'],
        ],
        'PENGATURAN' => [
            ['label' => 'Pengguna',  'icon' => 'user-group',          'route' => 'users.index',        'active' => 'users.*',       'permission' => 'users.view'],
            ['label' => 'Role & Akses', 'icon' => 'shield-check',      'route' => 'roles.index',        'active' => 'roles.*',       'permission' => 'roles.view'],
            ['label' => 'Konfigurasi',  'icon' => 'cog-6-tooth',       'route' => 'settings.index',     'active' => 'settings.*',    'permission' => 'settings.view'],
        ],
    ];

    $user = auth()->user();
    $logoUrl = $appSettings->logoUrl();
    $storeName = $appSettings->storeName();
@endphp

<aside
    class="fixed inset-y-0 left-0 z-40 flex flex-col border-r border-gray-200 bg-white transition-all duration-300"
    :class="[
        collapsed ? 'w-16' : 'w-60',
        mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
    ]"
>
    {{-- Brand / Logo --}}
    <div class="flex h-16 items-center gap-2 border-b border-gray-200 px-4">
        @if ($logoUrl)
            <img src="{{ $logoUrl }}" alt="{{ $storeName }}" class="h-9 w-9 flex-shrink-0 rounded-lg object-contain" />
        @else
            <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg bg-primary-500 text-white">
                <x-heroicon-s-bolt class="h-5 w-5" />
            </span>
        @endif
        <span class="truncate text-lg font-bold tracking-tight text-gray-900" x-show="!collapsed" x-transition.opacity>
            {{ $storeName }}
        </span>
    </div>

    {{-- Navigasi --}}
    <nav class="flex-1 space-y-4 overflow-y-auto px-2 py-4">
        @foreach ($menu as $groupLabel => $items)
            @php
                $visibleItems = collect($items)->filter(
                    fn ($item) => ! $user || $user->can($item['permission'])
                );
            @endphp

            @if ($visibleItems->isNotEmpty())
                <x-sidebar.group :label="$groupLabel">
                    @foreach ($visibleItems as $item)
                        <x-sidebar.item
                            :label="$item['label']"
                            :icon="$item['icon']"
                            :route="$item['route']"
                            :active="request()->routeIs($item['active'])"
                        />
                    @endforeach
                </x-sidebar.group>
            @endif
        @endforeach
    </nav>
</aside>
