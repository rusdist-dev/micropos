@props([
    'name' => null,
    'title' => null,
    'size' => 'md',
    'show' => 'open',
])

@php
    // $show adalah nama property Alpine boolean pada scope induk yang mengontrol modal.
    $sizes = [
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'lg' => 'max-w-2xl',
        'xl' => 'max-w-4xl',
    ];
    $maxWidth = $sizes[$size] ?? $sizes['md'];
@endphp

<template x-teleport="body">
    <div
        x-show="{{ $show }}"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-modal="true"
        role="dialog"
        @keydown.escape.window="{{ $show }} = false"
    >
        {{-- Backdrop --}}
        <div
            x-show="{{ $show }}"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-gray-900/50"
            @click="{{ $show }} = false"
        ></div>

        {{-- Panel --}}
        <div class="flex min-h-full items-center justify-center p-4">
            <div
                x-show="{{ $show }}"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                class="relative w-full {{ $maxWidth }} rounded-xl bg-white shadow-2xl"
                @click.stop
            >
                @if ($title)
                    <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $title }}</h3>
                        <button type="button" @click="{{ $show }} = false" class="rounded-md p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600">
                            <x-heroicon-o-x-mark class="h-5 w-5" />
                        </button>
                    </div>
                @endif

                <div class="px-6 py-5">
                    {{ $slot }}
                </div>

                @isset($footer)
                    <div class="flex items-center justify-end gap-2 border-t border-gray-200 px-6 py-4">
                        {{ $footer }}
                    </div>
                @endisset
            </div>
        </div>
    </div>
</template>
