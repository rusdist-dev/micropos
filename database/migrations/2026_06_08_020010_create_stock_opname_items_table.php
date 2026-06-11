<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained('stock_opnames')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->integer('system_qty');              // snapshot stok sistem saat dibuat
            $table->integer('counted_qty')->nullable(); // hasil hitung fisik (null = belum dihitung)
            $table->integer('difference')->default(0);  // counted - system (diisi saat finalize)
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->unique(['stock_opname_id', 'product_id']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opname_items');
    }
};
