@props([
    'align' => 'right',
    'width' => '48',
    'contentClasses' => 'py-1 bg-white',
    'direction' => 'down',
])

@php
    $alignmentClasses = match ($align) {
        'left' => 'origin-top-left left-0',
        'top' => 'origin-top',
        default => 'origin-top-right right-0',
    };

    $positionClasses = $direction === 'up' ? 'bottom-full mb-2' : 'mt-2';

    $width = match ($width) {
        '48' => 'w-48',
        '56' => 'w-56',
        '64' => 'w-64',
        default => $width,
    };
@endphp

<div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
    <div @click="open = ! open">
        {{ $trigger }}
    </div>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click="open = false"
        @click.outside="open = false"
        class="absolute z-50 {{ $positionClasses }} {{ $width }} rounded-lg shadow-lg ring-1 ring-black/5 {{ $alignmentClasses }}"
        style="display: none;"
    >
        <div class="overflow-hidden rounded-lg {{ $contentClasses }}">
            {{ $content }}
        </div>
    </div>
</div>
