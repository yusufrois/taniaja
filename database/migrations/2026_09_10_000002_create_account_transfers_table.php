<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Transfer antar akun (Kas ke Bank, dst) — juga jadi jurnal" —
     * roadmap Fase L5. Moving money between the company's OWN
     * Kas/Bank accounts doesn't change total assets, so this posts a
     * simple Debit-to / Credit-from journal entry between two asset
     * accounts (see AccountingPostingService::postAccountTransfer()).
     */
    public function up(): void
    {
        Schema::create('account_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('from_account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $table->foreignId('to_account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $table->decimal('amount', 18, 2);
            $table->date('date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_transfers');
    }
};
