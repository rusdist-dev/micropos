@props([
    'size' => 'md',
    'label' => null,
])

@php
    $sizes = [
        'sm' => 'h-4 w-4 border-2',
        'md' => 'h-6 w-6 border-2',
        'lg' => 'h-10 w-10 border-[3px]',
    ];
    $spinner = $sizes[$size] ?? $sizes['md'];
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center justify-center gap-2 text-gray-500']) }}>
    <span class="inline-block animate-spin rounded-full border-gray-200 border-t-primary-500 {{ $spinner }}"></span>
    @if ($label)
        <span class="text-sm">{{ $label }}</span>
    @endif
</div>
