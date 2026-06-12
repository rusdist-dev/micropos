<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Akses terpusat ke konfigurasi aplikasi. Di-bind sebagai singleton dan dibagikan
 * ke seluruh view sebagai $appSettings (lihat AppServiceProvider). Hasil query
 * di-cache selamanya dan di-flush saat ada perubahan.
 */
class AppSettings
{
    private const CACHE_KEY = 'app_settings';

    /** Kunci konfigurasi yang dapat diedit beserta nilai default. */
    public const DEFAULTS = [
        'store_name' => 'MicroPOS',
        'store_address' => '',
        'store_phone' => '',
        'receipt_footer' => 'Terima kasih atas kunjungan Anda',
        'store_logo' => '',
        'primary_color' => ColorPalette::DEFAULT_BASE,
    ];

    private ?array $loaded = null;

    /** @return array<string,string|null> */
    public function all(): array
    {
        if ($this->loaded !== null) {
            return $this->loaded;
        }

        $stored = Cache::rememberForever(self::CACHE_KEY, function () {
            try {
                if (! Schema::hasTable('settings')) {
                    return [];
                }

                return Setting::query()->pluck('value', 'key')->all();
            } catch (Throwable $e) {
                // Saat migrasi belum jalan / DB belum siap: jangan jatuhkan request.
                return [];
            }
        });

        return $this->loaded = array_merge(self::DEFAULTS, array_filter(
            $stored,
            fn ($v) => $v !== null && $v !== ''
        ));
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function storeName(): string
    {
        return $this->get('store_name') ?: 'MicroPOS';
    }

    public function storeAddress(): string
    {
        return (string) $this->get('store_address', '');
    }

    public function storePhone(): string
    {
        return (string) $this->get('store_phone', '');
    }

    public function receiptFooter(): string
    {
        return (string) $this->get('receipt_footer', '');
    }

    public function primaryColor(): string
    {
        return ColorPalette::normalize($this->get('primary_color'));
    }

    /** Path relatif logo pada disk public, atau null bila belum diatur. */
    public function logoPath(): ?string
    {
        $path = $this->get('store_logo');

        return $path !== '' ? $path : null;
    }

    /** URL publik logo (asset/storage), atau null bila belum diatur. */
    public function logoUrl(): ?string
    {
        $path = $this->logoPath();

        return $path ? asset('storage/'.ltrim($path, '/')) : null;
    }

    /** Tangga warna primary (shade => "R G B") untuk CSS variables. */
    public function palette(): array
    {
        return ColorPalette::generate($this->primaryColor());
    }

    /** Subset aman untuk dipakai di sisi klien (struk, chart). */
    public function forJs(): array
    {
        return [
            'storeName' => $this->storeName(),
            'storeAddress' => $this->storeAddress(),
            'storePhone' => $this->storePhone(),
            'receiptFooter' => $this->receiptFooter(),
            'logoUrl' => $this->logoUrl(),
            'primaryColor' => $this->primaryColor(),
        ];
    }

    /** Simpan banyak nilai sekaligus lalu flush cache. */
    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->loaded = null;
    }
}
