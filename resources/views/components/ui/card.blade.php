@props([
    'title' => null,
    'padding' => 'p-6',
])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-gray-200 bg-white shadow-sm']) }}>
    @if ($title || isset($actions))
        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
            @if ($title)
                <h3 class="text-base font-semibold text-gray-900">{{ $title }}</h3>
            @endif
            @isset($actions)
                <div class="flex items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    <div class="{{ $padding }}">
        {{ $slot }}
    </div>
</div>
