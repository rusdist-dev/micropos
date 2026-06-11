<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('returns', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->foreignId('transaction_id')->constrained('transactions')->restrictOnDelete();
            $table->foreignId('kasir_id')->constrained('users')->restrictOnDelete();
            $table->decimal('returned_total', 15, 2)->default(0);
            $table->decimal('exchange_total', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->default(0);        // exchange_total - returned_total
            $table->decimal('payment_amount', 15, 2)->default(0); // dibayar pelanggan bila balance > 0
            $table->decimal('refund_amount', 15, 2)->default(0);  // dikembalikan bila balance < 0
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('transaction_id');
            $table->index('kasir_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('returns');
    }
};
