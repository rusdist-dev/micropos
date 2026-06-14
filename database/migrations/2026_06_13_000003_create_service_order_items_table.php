<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_order_id')->constrained('service_orders')->cascadeOnDelete();
            $table->enum('item_type', ['product', 'service']);
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->string('item_name');
            // produk = harga modal (purchase_price); jasa = harga jasa saat order.
            $table->decimal('price_snapshot', 15, 2);
            $table->integer('qty')->default(1);
            $table->decimal('subtotal', 15, 2);
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->index('service_order_id');
            $table->index('product_id');
            $table->index('item_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_order_items');
    }
};
