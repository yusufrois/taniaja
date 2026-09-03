<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Each line is EITHER a debit OR a credit (never both, never
     * neither) — enforced in JournalEntryService, not at DB level
     * (SQLite/MySQL CHECK constraint support is inconsistent across
     * this app's target environments, so validation lives in PHP,
     * consistent with how the rest of this app validates business
     * rules). Across all of one entry's lines, SUM(debit) MUST equal
     * SUM(credit) — also enforced in JournalEntryService, this is the
     * core "double-entry" guarantee.
     *
     * softDeletes() here too — when a JournalEntry is voided
     * (destroy()), its lines are cascade-soft-deleted WITH it (see
     * JournalEntryController::destroy()), so ChartOfAccount::balance()
     * (which sums JournalEntryLine directly) correctly excludes voided
     * entries without needing a join back to journal_entries.
     */
    public function up(): void
    {
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('chart_of_account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $table->decimal('debit', 18, 2)->default(0);
            $table->decimal('credit', 18, 2)->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'chart_of_account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entry_lines');
    }
};
