<?php

namespace Database\Seeders;

use App\Models\CustomerType;
use Illuminate\Database\Seeder;

class CustomerTypeSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            ['name' => 'Pelanggan Umum', 'phone' => null, 'address' => null],
            ['name' => 'PT Maju Jaya', 'phone' => '021-5550123', 'address' => 'Jl. Industri No. 12, Jakarta'],
            ['name' => 'Toko Elektronik Sejahtera', 'phone' => '0812-3456-7890', 'address' => 'Jl. Pasar Baru No. 45, Bandung'],
        ];

        foreach ($customers as $customer) {
            CustomerType::updateOrCreate(['name' => $customer['name']], $customer);
        }
    }
}
