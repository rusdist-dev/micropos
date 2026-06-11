@props([
    'variant' => 'neutral',
    'size' => 'md',
])

@php
    $variants = [
        'primary' => 'bg-primary-50 text-primary-700 ring-primary-600/20',
        'warning' => 'bg-warning-50 text-warning-700 ring-warning-600/20',
        'danger'  => 'bg-danger-50 text-danger-700 ring-danger-600/20',
        'neutral' => 'bg-gray-100 text-gray-600 ring-gray-500/20',
    ];

    $sizes = [
        'sm' => 'px-2 py-0.5 text-xs',
        'md' => 'px-2.5 py-0.5 text-xs',
        'lg' => 'px-3 py-1 text-sm',
    ];

    $classes = 'inline-flex items-center gap-1 rounded-full font-medium ring-1 ring-inset '
        . ($variants[$variant] ?? $variants['neutral']) . ' '
        . ($sizes[$size] ?? $sizes['md']);
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</span>
