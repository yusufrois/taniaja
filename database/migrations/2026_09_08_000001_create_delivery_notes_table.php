<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Surat Jalan" — bukti barang keluar gudang, BEDA dari Sale
     * (Invoice = tagihan uang). sale_id nullable: barang bisa keluar
     * duluan (delivery) sebelum invoice terbit, atau tanpa invoice
     * sama sekali (sample/retur) — kalau ADA sale_id, status lunas/
     * belum otomatis ikut dari Sale.payment_status yang sudah ada,
     * tidak perlu status bayar terpisah di sini.
     */
    public function up(): void
    {
        Schema::create('delivery_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('delivery_number')->unique();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->date('date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_notes');
    }
};
