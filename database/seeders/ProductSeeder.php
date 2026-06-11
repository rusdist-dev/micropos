<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // 'types' menentukan tipe harga mana yang dimiliki produk (harga kini dinamis;
        // tidak semua produk wajib punya semua tipe). Faktor pengali per tipe di $factor.
        $products = [
            ['name' => 'Kabel UTP Cat6 (per meter)', 'brand' => 'Belden', 'sku' => 'KBL-UTP6', 'stock' => 500, 'min_stock' => 50, 'purchase_price' => 4500, 'base' => 7000, 'types' => ['umum', 'teknisi', 'grosir', 'mitra']],
            ['name' => 'Konektor RJ45', 'brand' => 'AMP', 'sku' => 'CON-RJ45', 'stock' => 800, 'min_stock' => 100, 'purchase_price' => 800, 'base' => 1500, 'types' => ['umum', 'teknisi', 'grosir', 'mitra']],
            ['name' => 'Switch Hub 8 Port', 'brand' => 'TP-Link', 'sku' => 'SW-8P', 'stock' => 25, 'min_stock' => 5, 'purchase_price' => 145000, 'base' => 185000, 'types' => ['umum', 'teknisi', 'grosir', 'mitra']],
            ['name' => 'Access Point Outdoor', 'brand' => 'Ubiquiti', 'sku' => 'AP-OUT', 'stock' => 12, 'min_stock' => 3, 'purchase_price' => 850000, 'base' => 1050000, 'types' => ['umum', 'teknisi', 'mitra']],
            ['name' => 'Router Mikrotik RB750', 'brand' => 'Mikrotik', 'sku' => 'RTR-750', 'stock' => 8, 'min_stock' => 4, 'purchase_price' => 620000, 'base' => 780000, 'types' => ['umum', 'teknisi', 'grosir']],
            ['name' => 'Tang Crimping', 'brand' => 'Krisbow', 'sku' => 'TL-CRMP', 'stock' => 4, 'min_stock' => 5, 'purchase_price' => 75000, 'base' => 120000, 'types' => ['umum']],
            ['name' => 'Kabel Power 1.5m', 'brand' => 'Generic', 'sku' => 'KBL-PWR', 'stock' => 150, 'min_stock' => 20, 'purchase_price' => 12000, 'base' => 22000, 'types' => ['umum', 'grosir']],
            ['name' => 'Adaptor PoE 24V', 'brand' => 'Ubiquiti', 'sku' => 'ADP-POE', 'stock' => 30, 'min_stock' => 10, 'purchase_price' => 65000, 'base' => 95000, 'types' => ['umum', 'teknisi', 'grosir', 'mitra']],
        ];

        $factor = ['umum' => 1.0, 'teknisi' => 0.95, 'grosir' => 0.90, 'mitra' => 0.85];

        foreach ($products as $data) {
            $base = $data['base'];
            $product = Product::updateOrCreate(
                ['sku' => $data['sku']],
                [
                    'name' => $data['name'],
                    'brand' => $data['brand'],
                    'stock' => $data['stock'],
                    'min_stock' => $data['min_stock'],
                    'purchase_price' => $data['purchase_price'],
                    'description' => 'Produk dummy untuk prototype Fase 1.',
                    'is_active' => true,
                ]
            );

            foreach ($data['types'] as $type) {
                $product->prices()->updateOrCreate(
                    ['price_type' => $type],
                    [
                        'price' => (int) round($base * ($factor[$type] ?? 1.0)),
                        // Default kasir = 'umum' bila tersedia, jika tidak tipe pertama.
                        'is_active_default' => $type === ($data['types'][0] === 'umum' ? 'umum' : $data['types'][0]),
                    ]
                );
            }
        }
    }
}
