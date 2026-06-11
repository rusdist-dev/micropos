@props([
    'label' => '',
    'icon' => 'home',
    'route' => '#',
    'active' => false,
])

@php
    $href = \Illuminate\Support\Facades\Route::has($route) ? route($route) : '#';
@endphp

<a
    href="{{ $href }}"
    @click="mobileOpen = false"
    :title="collapsed ? '{{ $label }}' : null"
    @class([
        'group relative flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
        'bg-primary-50 text-primary-700 border-l-4 border-primary-500 pl-2' => $active,
        'text-gray-600 hover:bg-gray-100 hover:text-gray-900' => ! $active,
    ])
    :class="collapsed ? 'justify-center' : ''"
>
    <x-dynamic-component
        :component="'heroicon-o-' . $icon"
        @class([
            'h-5 w-5 flex-shrink-0',
            'text-primary-600' => $active,
            'text-gray-400 group-hover:text-gray-600' => ! $active,
        ])
    />
    <span x-show="!collapsed" x-transition.opacity class="truncate">{{ $label }}</span>
</a>
