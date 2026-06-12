{{--
    Injeksi tema runtime: override channel warna primary + expose konfigurasi toko
    ke sisi klien (window.posSettings) untuk struk & chart. Disisipkan di <head>
    seluruh layout, sebelum @vite. $appSettings dibagikan global via AppServiceProvider.
--}}
<style>
    :root {
        @foreach ($appSettings->palette() as $shade => $rgb)
        --color-primary-{{ $shade }}: {{ $rgb }};
        @endforeach
    }
</style>
<script>
    window.posSettings = @json($appSettings->forJs());
</script>
