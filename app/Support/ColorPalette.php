<?php

namespace App\Support;

/**
 * Menghasilkan tangga warna (shade 50–950) gaya Tailwind dari satu warna dasar (hex),
 * lalu mengeluarkannya sebagai channel "R G B" agar bisa dipakai sebagai CSS variable:
 *   --color-primary-500: 20 184 166;  ->  rgb(var(--color-primary-500) / <alpha-value>)
 *
 * Shade 500 = warna dasar persis. Shade lebih terang dicampur dengan putih,
 * lebih gelap dicampur dengan hitam berdasar rasio tetap.
 */
class ColorPalette
{
    /** Warna primary bawaan (teal-500), selaras dengan tailwind.config.js sebelumnya. */
    public const DEFAULT_BASE = '#14b8a6';

    /** Ramp default eksak agar tampilan tema bawaan tidak berubah sama sekali. */
    private const DEFAULT_RAMP = [
        50 => '#f0fdfa', 100 => '#ccfbf1', 200 => '#99f6e4', 300 => '#5eead4',
        400 => '#2dd4bf', 500 => '#14b8a6', 600 => '#0d9488', 700 => '#0f766e',
        800 => '#115e59', 900 => '#134e4a', 950 => '#042f2e',
    ];

    /** Rasio campur menuju putih (shade terang) / hitam (shade gelap). */
    private const MIX = [
        50 => ['#ffffff', 0.95], 100 => ['#ffffff', 0.88], 200 => ['#ffffff', 0.74],
        300 => ['#ffffff', 0.55], 400 => ['#ffffff', 0.30], 500 => [null, 0.0],
        600 => ['#000000', 0.12], 700 => ['#000000', 0.28], 800 => ['#000000', 0.42],
        900 => ['#000000', 0.55], 950 => ['#000000', 0.72],
    ];

    /**
     * @return array<int,string> shade => "R G B"
     */
    public static function generate(?string $hex): array
    {
        $hex = self::normalize($hex);

        // Tema bawaan: pakai ramp eksak.
        if ($hex === self::DEFAULT_BASE) {
            return array_map(fn ($c) => self::toChannels($c), self::DEFAULT_RAMP);
        }

        $base = self::toRgb($hex);
        $palette = [];
        foreach (self::MIX as $shade => [$target, $ratio]) {
            if ($target === null) {
                $palette[$shade] = "{$base[0]} {$base[1]} {$base[2]}";
                continue;
            }
            $t = self::toRgb($target);
            $palette[$shade] = implode(' ', [
                (int) round($base[0] + ($t[0] - $base[0]) * $ratio),
                (int) round($base[1] + ($t[1] - $base[1]) * $ratio),
                (int) round($base[2] + ($t[2] - $base[2]) * $ratio),
            ]);
        }

        return $palette;
    }

    /** Normalisasi hex ke bentuk "#rrggbb" huruf kecil; fallback ke warna bawaan jika invalid. */
    public static function normalize(?string $hex): string
    {
        $hex = strtolower(trim((string) $hex));
        if (preg_match('/^#?([0-9a-f]{3})$/', $hex, $m)) {
            $hex = '#'.$m[1][0].$m[1][0].$m[1][1].$m[1][1].$m[1][2].$m[1][2];
        }
        if (! preg_match('/^#[0-9a-f]{6}$/', $hex)) {
            return self::DEFAULT_BASE;
        }

        return $hex;
    }

    /** @return array{0:int,1:int,2:int} */
    private static function toRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    private static function toChannels(string $hex): string
    {
        return implode(' ', self::toRgb($hex));
    }
}
