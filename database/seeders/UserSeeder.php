<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@pos.test'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $admin->syncRoles('admin');

        $kasir = User::updateOrCreate(
            ['email' => 'kasir@pos.test'],
            [
                'name' => 'Kasir 1',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );
        $kasir->syncRoles('kasir');
    }
}
