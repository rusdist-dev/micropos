@props([
    'page' => 'meta.current_page',
    'lastPage' => 'meta.last_page',
    'total' => 'meta.total',
    'perPage' => 'meta.per_page',
    'handler' => 'goToPage',
])

{{--
    Pagination berbasis Alpine. Seluruh prop adalah ekspresi Alpine yang
    dievaluasi pada scope induk:
    - $page      : nomor halaman aktif
    - $lastPage  : jumlah halaman terakhir
    - $handler   : nama fungsi(page) untuk pindah halaman
--}}
<div class="flex flex-col items-center justify-between gap-3 text-sm sm:flex-row" x-show="{{ $lastPage }} > 0">
    <p class="text-gray-500">
        Menampilkan halaman <span class="font-medium text-gray-700" x-text="{{ $page }}"></span>
        dari <span class="font-medium text-gray-700" x-text="{{ $lastPage }}"></span>
        <span x-show="typeof ({{ $total }}) !== 'undefined'">
            (<span x-text="{{ $total }}"></span> data)
        </span>
    </p>

    <nav class="inline-flex items-center gap-1" aria-label="Pagination">
        <button
            type="button"
            @click="{{ $handler }}({{ $page }} - 1)"
            :disabled="{{ $page }} <= 1"
            class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-300 text-gray-500 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
        >
            <x-heroicon-o-chevron-left class="h-4 w-4" />
        </button>

        <template x-for="p in Array.from({ length: {{ $lastPage }} }, (_, i) => i + 1)" :key="p">
            <button
                type="button"
                @click="{{ $handler }}(p)"
                x-show="p === 1 || p === ({{ $lastPage }}) || Math.abs(p - ({{ $page }})) <= 1"
                :class="p === ({{ $page }})
                    ? 'bg-primary-600 text-white border-primary-600'
                    : 'border-gray-300 text-gray-600 hover:bg-gray-50'"
                class="inline-flex h-8 min-w-8 items-center justify-center rounded-md border px-2 text-sm transition"
                x-text="p"
            ></button>
        </template>

        <button
            type="button"
            @click="{{ $handler }}({{ $page }} + 1)"
            :disabled="{{ $page }} >= ({{ $lastPage }})"
            class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-300 text-gray-500 transition hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
        >
            <x-heroicon-o-chevron-right class="h-4 w-4" />
        </button>
    </nav>
</div>
