<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supply_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supply_id')->constrained('supplies')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->integer('qty');
            $table->decimal('purchase_price', 15, 2)->nullable(); // null = biarkan modal produk
            $table->json('prices')->nullable();                   // snapshot harga jual diterapkan: [{price_type, price}]
            $table->decimal('line_cost', 15, 2)->default(0);      // qty * modal efektif
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->index('supply_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supply_items');
    }
};
