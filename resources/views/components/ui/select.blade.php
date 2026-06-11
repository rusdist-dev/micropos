@props([
    'name' => null,
    'label' => null,
    'options' => [],
    'selected' => null,
    'placeholder' => null,
    'error' => null,
    'required' => false,
])

<div>
    @if ($label)
        <label @if ($name) for="{{ $name }}" @endif class="mb-1 block text-sm font-medium text-gray-700">
            {{ $label }}
            @if ($required)<span class="text-danger-500">*</span>@endif
        </label>
    @endif

    <select
        @if ($name) name="{{ $name }}" id="{{ $name }}" @endif
        @required($required)
        {{ $attributes->merge(['class' => 'block w-full rounded-lg border-gray-300 text-sm shadow-sm transition focus:border-primary-500 focus:ring-primary-500 ' . ($error ? 'border-danger-400 focus:border-danger-500 focus:ring-danger-500' : '')]) }}
    >
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif

        @if (count($options))
            @foreach ($options as $value => $text)
                <option value="{{ $value }}" @selected((string) $selected === (string) $value)>{{ $text }}</option>
            @endforeach
        @else
            {{ $slot }}
        @endif
    </select>

    @if ($error)
        <p class="mt-1 text-xs text-danger-600">{{ $error }}</p>
    @endif
</div>
