<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fondasi seluruh sistem akuntansi double-entry (Fase L). Setiap
     * akun punya `type` yang menentukan bagaimana ia muncul di Neraca
     * (Aset/Kewajiban/Modal) vs Laba Rugi (Pendapatan/Beban) — lihat
     * StandardChartOfAccountsSeeder untuk daftar akun standar yang
     * dipakai.
     *
     * SENGAJA TANPA HIERARKI (parent/child akun) — daftar akun standar
     * ini flat, cukup untuk kebutuhan UMKM. Kalau nanti butuh sub-akun
     * berjenjang, itu perluasan terpisah (Aturan #43).
     */
    public function up(): void
    {
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('code', 20); // e.g. "1100"
            $table->string('name'); // e.g. "Kas"
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};
