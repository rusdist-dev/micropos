@props([
    'title' => '',
    'value' => '',
    'icon' => 'chart-bar',
    'trend' => null,
    'color' => 'primary',
])

@php
    $colors = [
        'primary' => 'bg-primary-50 text-primary-600',
        'warning' => 'bg-warning-50 text-warning-600',
        'danger'  => 'bg-danger-50 text-danger-600',
        'neutral' => 'bg-gray-100 text-gray-600',
    ];
    $iconClasses = $colors[$color] ?? $colors['primary'];

    $trendUp = $trend !== null && $trend >= 0;
@endphp

<div {{ $attributes->merge(['class' => 'rounded-xl border border-gray-200 bg-white p-5 shadow-sm']) }}>
    <div class="flex items-start justify-between">
        <div class="min-w-0">
            <p class="truncate text-sm font-medium text-gray-500">{{ $title }}</p>
            <p class="mt-2 text-2xl font-bold text-gray-900">{{ $value }}</p>
        </div>
        <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-lg {{ $iconClasses }}">
            <x-dynamic-component :component="'heroicon-o-' . $icon" class="h-6 w-6" />
        </span>
    </div>

    @if (! is_null($trend))
        <div class="mt-3 flex items-center gap-1 text-xs">
            <span @class([
                'inline-flex items-center gap-0.5 font-medium',
                'text-primary-600' => $trendUp,
                'text-danger-600' => ! $trendUp,
            ])>
                <x-dynamic-component
                    :component="$trendUp ? 'heroicon-s-arrow-trending-up' : 'heroicon-s-arrow-trending-down'"
                    class="h-3.5 w-3.5"
                />
                {{ abs($trend) }}%
            </span>
            <span class="text-gray-400">dari kemarin</span>
        </div>
    @endif
</div>
