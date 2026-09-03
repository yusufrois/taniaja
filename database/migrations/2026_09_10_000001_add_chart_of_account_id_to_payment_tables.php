<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Roadmap Fase L5 — "setiap pembayaran WAJIB pilih dari akun mana
     * uangnya masuk/keluar". Nullable + backward compatible: if not
     * specified, AccountingPostingService falls back to the default
     * Kas (1100) — so existing integrations/tests that don't pass this
     * field keep working exactly as before.
     *
     * 'debts' included too (not just debt_payments) — RECEIVING a loan
     * is just as much a cash-side event as paying one back.
     */
    private const TABLES = ['sale_payments', 'purchase_payments', 'expenses', 'debt_payments', 'capital_transactions', 'debts'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreignId('chart_of_account_id')->nullable()
                    ->after('amount')->constrained('chart_of_accounts')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropConstrainedForeignId('chart_of_account_id');
            });
        }
    }
};
