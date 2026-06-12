<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Kabel & Konektor', 'description' => 'Kabel jaringan, power, dan konektor.'],
            ['name' => 'Perangkat Jaringan', 'description' => 'Switch, router, access point, dan sejenisnya.'],
            ['name' => 'Aksesoris', 'description' => 'Adaptor, PoE, dan aksesoris pendukung.'],
            ['name' => 'Peralatan', 'description' => 'Alat kerja teknisi.'],
        ];

        foreach ($categories as $data) {
            Category::firstOrCreate(['name' => $data['name']], $data);
        }
    }
}
