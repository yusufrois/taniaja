<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Roadmap tambahan #6 — "saat input aset tetap apakah otomatis
     * mencatat pengeluaran dan mengurangi saldo". Nullable, same
     * pattern as Expense/Debt/CapitalTransaction (Fase L5) — falls
     * back to default Kas (1100) when not chosen.
     */
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->foreignId('chart_of_account_id')->nullable()->after('value')
                ->constrained('chart_of_accounts')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('chart_of_account_id');
        });
    }
};
