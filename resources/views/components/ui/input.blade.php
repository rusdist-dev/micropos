@props([
    'type' => 'text',
    'name' => null,
    'label' => null,
    'placeholder' => null,
    'error' => null,
    'required' => false,
    'value' => null,
    'hint' => null,
])

<div>
    @if ($label)
        <label @if ($name) for="{{ $name }}" @endif class="mb-1 block text-sm font-medium text-gray-700">
            {{ $label }}
            @if ($required)<span class="text-danger-500">*</span>@endif
        </label>
    @endif

    <input
        type="{{ $type }}"
        @if ($name) name="{{ $name }}" id="{{ $name }}" @endif
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if (! is_null($value)) value="{{ $value }}" @endif
        @required($required)
        {{ $attributes->merge(['class' => 'block w-full rounded-lg border-gray-300 text-sm shadow-sm transition focus:border-primary-500 focus:ring-primary-500 ' . ($error ? 'border-danger-400 focus:border-danger-500 focus:ring-danger-500' : '')]) }}
    />

    @if ($hint && ! $error)
        <p class="mt-1 text-xs text-gray-400">{{ $hint }}</p>
    @endif

    @if ($error)
        <p class="mt-1 text-xs text-danger-600">{{ $error }}</p>
    @endif
</div>
