<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tambah nilai 'service_out' & 'service_in' ke enum stock_movements.type
     * agar pergerakan stok dari servis tercatat terpisah dari penjualan/retur.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE stock_movements MODIFY COLUMN type ENUM('sale', 'supply', 'opname', 'return_in', 'return_out', 'adjustment', 'service_out', 'service_in')");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE stock_movements MODIFY COLUMN type ENUM('sale', 'supply', 'opname', 'return_in', 'return_out', 'adjustment')");
    }
};
