@props([
    'placeholder' => 'Cari...',
    'model' => 'search',
    'debounce' => '300ms',
])

{{-- Mengikat ke property Alpine ($model) pada scope induk dengan debounce. --}}
<div class="relative">
    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
        <x-heroicon-o-magnifying-glass class="h-5 w-5 text-gray-400" />
    </span>

    <input
        type="text"
        x-model.debounce.{{ $debounce }}="{{ $model }}"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge(['class' => 'block w-full rounded-lg border-gray-300 pl-10 pr-9 text-sm shadow-sm transition focus:border-primary-500 focus:ring-primary-500']) }}
    />

    <button
        type="button"
        x-show="{{ $model }} && {{ $model }}.length"
        x-cloak
        @click="{{ $model }} = ''"
        class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 transition hover:text-gray-600"
    >
        <x-heroicon-o-x-mark class="h-4 w-4" />
    </button>
</div>
