@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
    'icon' => null,
    'disabled' => false,
])

@php
    $base = 'inline-flex items-center justify-center gap-2 font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-offset-1 disabled:opacity-50 disabled:pointer-events-none';

    $variants = [
        'primary' => 'bg-primary-600 text-white hover:bg-primary-700 focus:ring-primary-500',
        'warning' => 'bg-warning-500 text-white hover:bg-warning-600 focus:ring-warning-400',
        'danger'  => 'bg-danger-600 text-white hover:bg-danger-700 focus:ring-danger-500',
        'outline' => 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:ring-primary-500',
        'ghost'   => 'text-gray-600 hover:bg-gray-100 focus:ring-primary-500',
    ];

    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-5 py-2.5 text-base',
    ];

    $iconSize = ['sm' => 'h-4 w-4', 'md' => 'h-5 w-5', 'lg' => 'h-5 w-5'][$size] ?? 'h-5 w-5';

    $classes = trim($base . ' ' . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']));
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)
            <x-dynamic-component :component="'heroicon-o-' . $icon" class="{{ $iconSize }}" />
        @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" @disabled($disabled) {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)
            <x-dynamic-component :component="'heroicon-o-' . $icon" class="{{ $iconSize }}" />
        @endif
        {{ $slot }}
    </button>
@endif
