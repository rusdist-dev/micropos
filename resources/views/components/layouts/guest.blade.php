@props([
    'title' => null,
])

@php
    $logoUrl = $appSettings->logoUrl();
    $storeName = $appSettings->storeName();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ? $title . ' — ' : '' }}{{ $storeName }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('partials.theme')
</head>
<body class="font-sans text-gray-800 antialiased">
    <div class="flex min-h-screen flex-col items-center justify-center bg-gradient-to-br from-primary-50 via-gray-50 to-warning-50 px-4 py-10">
        <div class="mb-6 flex items-center gap-2">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $storeName }}" class="h-11 w-11 rounded-xl object-contain shadow-lg shadow-gray-200/60" />
            @else
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-500 text-white shadow-lg shadow-primary-500/30">
                    <x-heroicon-s-bolt class="h-6 w-6" />
                </span>
            @endif
            <span class="text-2xl font-bold tracking-tight text-gray-900">{{ $storeName }}</span>
        </div>

        <div class="w-full max-w-md overflow-hidden rounded-2xl bg-white px-6 py-8 shadow-xl shadow-gray-200/60 sm:px-8">
            {{ $slot }}
        </div>

        <p class="mt-6 text-xs text-gray-400">&copy; {{ date('Y') }} {{ $storeName }} — Point of Sale</p>
    </div>
</body>
</html>
