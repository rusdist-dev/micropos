<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_id')->constrained('returns')->cascadeOnDelete();
            $table->enum('direction', ['returned', 'exchange']);
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('transaction_item_id')->nullable()->constrained('transaction_items')->nullOnDelete();
            $table->string('item_name');
            $table->string('price_type_used', 20)->nullable();
            $table->decimal('price_snapshot', 15, 2);
            $table->integer('qty');
            $table->decimal('subtotal', 15, 2);
            $table->boolean('restock')->default(false); // hanya relevan utk direction=returned
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->index(['return_id', 'direction']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_items');
    }
};
