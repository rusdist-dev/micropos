<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->enum('item_type', ['product', 'service']);
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->string('item_name');
            $table->string('price_type_used', 20)->nullable();
            $table->decimal('price_snapshot', 15, 2);
            $table->integer('qty')->default(1);
            $table->decimal('subtotal', 15, 2);
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->index('transaction_id');
            $table->index('product_id');
            $table->index('item_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_items');
    }
};
