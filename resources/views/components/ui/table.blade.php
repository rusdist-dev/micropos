@props([])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm']) }}>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            @isset($head)
                <thead class="bg-gray-50">
                    <tr>
                        {{ $head }}
                    </tr>
                </thead>
            @endisset

            <tbody class="divide-y divide-gray-100 bg-white">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @isset($footer)
        <div class="border-t border-gray-200 px-4 py-3">
            {{ $footer }}
        </div>
    @endisset
</div>
