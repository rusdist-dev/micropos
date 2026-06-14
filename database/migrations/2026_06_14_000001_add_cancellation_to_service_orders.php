<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            // Biaya pembatalan = bagian DP yang ditahan (diakui sebagai pendapatan).
            // Sisa (paid_amount - cancellation_fee) adalah refund ke pelanggan.
            $table->decimal('cancellation_fee', 15, 2)->default(0)->after('cancel_note');
            $table->timestamp('canceled_at')->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->dropColumn(['cancellation_fee', 'canceled_at']);
        });
    }
};
