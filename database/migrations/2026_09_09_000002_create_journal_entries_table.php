<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One journal entry = one transaction, made of >=2 balanced lines
     * (see journal_entry_lines). reference_type/reference_id are
     * nullable placeholders for Fase L2 (auto-posting from Sale/
     * Purchase/Expense/etc.) — for L1, every entry is manual, both
     * columns stay null.
     */
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->date('date');
            $table->string('description');
            $table->string('reference_type')->nullable(); // e.g. "sale", "purchase" — used from Fase L2 onward
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes(); // void, don't erase — Section 30

            $table->index(['company_id', 'date']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
