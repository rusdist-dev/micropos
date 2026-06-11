@props([
    'align' => 'left',
])

@php
    $alignClass = ['left' => 'text-left', 'center' => 'text-center', 'right' => 'text-right'][$align] ?? 'text-left';
@endphp

<td {{ $attributes->merge(['class' => 'whitespace-nowrap px-4 py-1 text-sm text-gray-700 ' . $alignClass]) }}>
    {{ $slot }}
</td>
