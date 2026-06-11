<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()['cache']->forget('spatie.permission.cache');

        $permissions = [
            'dashboard.view',
            'products.view', 'products.create', 'products.edit', 'products.delete',
            'price-types.view', 'price-types.create', 'price-types.edit', 'price-types.delete',
            'suppliers.view', 'suppliers.create', 'suppliers.edit', 'suppliers.delete',
            'stock-opnames.view', 'stock-opnames.create', 'stock-opnames.edit', 'stock-opnames.finalize',
            'supplies.view', 'supplies.create',
            'returns.view', 'returns.create', 'returns.view-all',
            'customers.view', 'customers.create', 'customers.edit', 'customers.delete',
            'services.view', 'services.create', 'services.edit', 'services.delete',
            'transactions.create', 'transactions.view', 'transactions.view-all',
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'roles.view', 'roles.create', 'roles.edit', 'roles.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $kasir = Role::firstOrCreate(['name' => 'kasir', 'guard_name' => 'web']);

        // Admin: semua permission
        $admin->syncPermissions($permissions);

        // Kasir: subset terbatas (price-types.view untuk dropdown tipe harga di kasir)
        $kasir->syncPermissions([
            'dashboard.view',
            'products.view',
            'price-types.view',
            'customers.view', 'customers.create',
            'services.view',
            'transactions.create', 'transactions.view',
            'returns.view', 'returns.create',
        ]);
    }
}
