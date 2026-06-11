<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->enum('status', ['posted'])->default('posted');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('supplier_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplies');
    }
};
