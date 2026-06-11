@props([
    'align' => 'left',
])

@php
    $alignClass = ['left' => 'text-left', 'center' => 'text-center', 'right' => 'text-right'][$align] ?? 'text-left';
@endphp

<th {{ $attributes->merge(['class' => 'px-4 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 ' . $alignClass]) }}>
    {{ $slot }}
</th>
