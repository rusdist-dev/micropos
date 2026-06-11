<?php

namespace Database\Seeders;

use App\Models\PriceType;
use Illuminate\Database\Seeder;

class PriceTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'umum',    'name' => 'Umum',    'sort_order' => 1],
            ['code' => 'teknisi', 'name' => 'Teknisi', 'sort_order' => 2],
            ['code' => 'grosir',  'name' => 'Grosir',  'sort_order' => 3],
            ['code' => 'mitra',   'name' => 'Mitra',   'sort_order' => 4],
        ];

        foreach ($types as $type) {
            PriceType::updateOrCreate(
                ['code' => $type['code']],
                array_merge($type, ['is_active' => true])
            );
        }
    }
}
