<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            ['name' => 'PT Distributor Jaringan', 'phone' => '021-5550100', 'address' => 'Jl. Gudang No. 8, Jakarta'],
            ['name' => 'CV Sumber Kabel', 'phone' => '022-7770200', 'address' => 'Jl. Industri No. 5, Bandung'],
            ['name' => 'Toko Grosir Elektronik', 'phone' => '0813-1111-2222', 'address' => 'Jl. Pasar No. 12, Surabaya'],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::updateOrCreate(['name' => $supplier['name']], array_merge($supplier, ['is_active' => true]));
        }
    }
}
