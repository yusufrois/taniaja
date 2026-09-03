<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Roadmap tambahan (permintaan pengguna) — "jadwal 24 bulan
     * otomatis (tentukan lama hutang saat input peminjaman)".
     * Nullable: a debt WITHOUT an installment plan (lump-sum loan,
     * pay whenever) still works exactly as before — this is purely
     * additive, no existing behavior changes for debts that don't set it.
     */
    public function up(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            $table->unsignedSmallInteger('installment_months')->nullable()->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            $table->dropColumn('installment_months');
        });
    }
};
