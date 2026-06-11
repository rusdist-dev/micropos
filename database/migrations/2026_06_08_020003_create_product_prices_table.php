<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            // Kode tipe harga (master price_types.code). Varchar agar tipe harga bebas/dinamis.
            $table->string('price_type', 50);
            $table->decimal('price', 15, 2)->default(0);
            $table->boolean('is_active_default')->default(false);
            $table->timestamps();

            $table->unique(['product_id', 'price_type']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
