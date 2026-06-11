@props([
    'label' => '',
])

<div x-data="{ open: true }" class="space-y-1">
    {{-- Label group: jadi tombol accordion saat sidebar expanded, tersembunyi saat collapsed --}}
    <button
        type="button"
        @click="open = !open"
        x-show="!collapsed"
        x-transition.opacity
        class="flex w-full items-center justify-between px-3 py-1 text-xs font-semibold uppercase tracking-wider text-gray-400 transition hover:text-gray-600"
    >
        <span>{{ $label }}</span>
        <x-heroicon-o-chevron-down class="h-3.5 w-3.5 transition-transform duration-200" ::class="open ? '' : '-rotate-90'" />
    </button>

    {{-- Pemisah tipis saat collapsed (menggantikan label) --}}
    <div x-show="collapsed" class="mx-3 border-t border-gray-100"></div>

    <div x-show="open || collapsed" x-transition.opacity class="space-y-0.5">
        {{ $slot }}
    </div>
</div>
