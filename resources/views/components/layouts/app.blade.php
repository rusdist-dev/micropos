@props([
    'title' => null,
    'breadcrumbs' => [],
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? $title . ' — ' : '' }}{{ config('app.name', 'MicroPOS') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-gray-800">
    <div
        x-data="{
            collapsed: false,
            mobileOpen: false,
            init() {
                this.collapsed = localStorage.getItem('sidebar-collapsed') === 'true';
            },
            toggleCollapse() {
                this.collapsed = !this.collapsed;
                localStorage.setItem('sidebar-collapsed', this.collapsed);
            },
        }"
        class="min-h-screen bg-gray-50"
    >
        {{-- Overlay untuk mode mobile --}}
        <div
            x-show="mobileOpen"
            x-transition.opacity
            @click="mobileOpen = false"
            class="fixed inset-0 z-30 bg-gray-900/50 lg:hidden"
            style="display: none;"
        ></div>

        {{-- Sidebar --}}
        <x-sidebar.index />

        {{-- Area konten --}}
        <div
            class="flex min-h-screen flex-col transition-all duration-300"
            :class="collapsed ? 'lg:pl-16' : 'lg:pl-60'"
        >
            {{-- Topbar --}}
            <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-gray-200 bg-white px-4 sm:px-6">
                <div class="flex items-center gap-3">
                    {{-- Toggle: collapse di desktop, drawer di mobile --}}
                    <button
                        type="button"
                        @click="window.innerWidth >= 1024 ? toggleCollapse() : (mobileOpen = !mobileOpen)"
                        class="inline-flex items-center justify-center rounded-md p-2 text-gray-500 transition hover:bg-gray-100 hover:text-gray-700"
                        aria-label="Toggle sidebar"
                    >
                        <x-heroicon-o-bars-3 class="h-6 w-6" />
                    </button>

                    {{-- Breadcrumb --}}
                    <nav class="hidden items-center text-sm sm:flex" aria-label="Breadcrumb">
                        <ol class="flex items-center gap-1.5 text-gray-500">
                            <li>
                                <a href="{{ route('dashboard') }}" class="transition hover:text-primary-600">Home</a>
                            </li>
                            @foreach ($breadcrumbs as $label => $url)
                                <li class="flex items-center gap-1.5">
                                    <x-heroicon-o-chevron-right class="h-4 w-4 text-gray-300" />
                                    @if ($url && ! $loop->last)
                                        <a href="{{ $url }}" class="transition hover:text-primary-600">{{ $label }}</a>
                                    @else
                                        <span class="font-medium text-gray-700">{{ is_string($label) ? $label : $url }}</span>
                                    @endif
                                </li>
                            @endforeach
                            @if (empty($breadcrumbs) && $title)
                                <li class="flex items-center gap-1.5">
                                    <x-heroicon-o-chevron-right class="h-4 w-4 text-gray-300" />
                                    <span class="font-medium text-gray-700">{{ $title }}</span>
                                </li>
                            @endif
                        </ol>
                    </nav>
                </div>

                {{-- User dropdown --}}
                <x-ui.dropdown align="right" width="48">
                    <x-slot:trigger>
                        <button class="flex items-center gap-2 rounded-full p-1 pr-2 transition hover:bg-gray-100">
                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-500 text-sm font-semibold text-white">
                                {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}
                            </span>
                            <span class="hidden text-left sm:block">
                                <span class="block text-sm font-medium leading-tight text-gray-800">{{ auth()->user()?->name }}</span>
                                <span class="block text-xs leading-tight text-gray-500">{{ auth()->user()?->getRoleNames()->first() ?? 'Pengguna' }}</span>
                            </span>
                            <x-heroicon-o-chevron-down class="hidden h-4 w-4 text-gray-400 sm:block" />
                        </button>
                    </x-slot:trigger>

                    <x-slot:content>
                        <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 transition hover:bg-gray-50">
                            <x-heroicon-o-user-circle class="h-5 w-5 text-gray-400" />
                            Profil
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-danger-600 transition hover:bg-danger-50">
                                <x-heroicon-o-arrow-right-on-rectangle class="h-5 w-5" />
                                Keluar
                            </button>
                        </form>
                    </x-slot:content>
                </x-ui.dropdown>
            </header>

            {{-- Konten halaman --}}
            <main class="flex-1 p-4 sm:p-6">
                {{ $slot }}
            </main>
        </div>

        {{-- Toast global --}}
        <x-ui.toast-container />
    </div>
</body>
</html>
