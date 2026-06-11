@props([
    'variant' => 'info',
    'dismissible' => false,
    'title' => null,
])

@php
    $variants = [
        'info'    => ['wrap' => 'bg-primary-50 text-primary-800 border-primary-200', 'icon' => 'information-circle', 'iconColor' => 'text-primary-500'],
        'success' => ['wrap' => 'bg-primary-50 text-primary-800 border-primary-200', 'icon' => 'check-circle',       'iconColor' => 'text-primary-500'],
        'warning' => ['wrap' => 'bg-warning-50 text-warning-800 border-warning-200', 'icon' => 'exclamation-triangle','iconColor' => 'text-warning-500'],
        'danger'  => ['wrap' => 'bg-danger-50 text-danger-800 border-danger-200',    'icon' => 'x-circle',            'iconColor' => 'text-danger-500'],
    ];
    $config = $variants[$variant] ?? $variants['info'];
@endphp

<div
    @if ($dismissible) x-data="{ show: true }" x-show="show" @endif
    {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-lg border px-4 py-3 text-sm ' . $config['wrap']]) }}
    role="alert"
>
    <x-dynamic-component :component="'heroicon-o-' . $config['icon']" class="mt-0.5 h-5 w-5 flex-shrink-0 {{ $config['iconColor'] }}" />

    <div class="flex-1">
        @if ($title)
            <p class="font-semibold">{{ $title }}</p>
        @endif
        <div @class(['mt-0.5' => $title])>{{ $slot }}</div>
    </div>

    @if ($dismissible)
        <button type="button" @click="show = false" class="flex-shrink-0 rounded p-0.5 opacity-60 transition hover:opacity-100">
            <x-heroicon-o-x-mark class="h-4 w-4" />
        </button>
    @endif
</div>
