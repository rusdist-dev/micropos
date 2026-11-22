<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /** Tambah permission `transactions.edit` dan berikan ke role admin. */
    public function up(): void
    {
        app()['cache']->forget('spatie.permission.cache');

        $permission = Permission::firstOrCreate(['name' => 'transactions.edit', 'guard_name' => 'web']);
        Role::where('name', 'admin')->where('guard_name', 'web')->first()?->givePermissionTo($permission);
    }

    public function down(): void
    {
        app()['cache']->forget('spatie.permission.cache');
        Permission::where('name', 'transactions.edit')->where('guard_name', 'web')->delete();
    }
};
