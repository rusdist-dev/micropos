<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_orders', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->foreignId('customer_id')->nullable()->constrained('customer_types')->nullOnDelete();
            $table->foreignId('technician_id')->nullable()->constrained('technicians')->nullOnDelete();
            // operator = admin/pengguna yang membuat order servis ("Operator" di riwayat).
            $table->foreignId('operator_id')->constrained('users')->restrictOnDelete();
            // subtotal = jumlah item (harga modal produk + jasa) sebelum diskon;
            // discount = potongan Rp; total = subtotal - discount.
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            // paid_amount = akumulasi pembayaran (DP awal + pelunasan).
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->enum('payment_status', ['belum_bayar', 'dp', 'lunas'])->default('belum_bayar');
            $table->enum('service_status', ['process', 'selesai', 'batal'])->default('process');
            $table->date('due_date')->nullable();           // tenggang waktu servis
            $table->timestamp('completed_at')->nullable();  // tanggal selesai
            $table->text('cancel_note')->nullable();        // keterangan pembatalan
            $table->text('note')->nullable();
            $table->timestamps();                            // created_at = tanggal transaksi

            $table->index('service_status');
            $table->index('payment_status');
            $table->index('created_at');
            $table->index('due_date');
            $table->index('technician_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_orders');
    }
};
