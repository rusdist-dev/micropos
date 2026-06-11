<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['name' => 'Instalasi Jaringan LAN', 'default_price' => 250000, 'description' => 'Pemasangan jaringan LAN per titik.'],
            ['name' => 'Crimping Kabel UTP', 'default_price' => 5000, 'description' => 'Pasang konektor RJ45 per ujung kabel.'],
            ['name' => 'Setting Mikrotik', 'default_price' => 350000, 'description' => 'Konfigurasi router Mikrotik.'],
            ['name' => 'Survey Lokasi', 'default_price' => 150000, 'description' => 'Survey kebutuhan jaringan di lokasi.'],
        ];

        foreach ($services as $service) {
            Service::updateOrCreate(
                ['name' => $service['name']],
                array_merge($service, ['is_active' => true])
            );
        }
    }
}
