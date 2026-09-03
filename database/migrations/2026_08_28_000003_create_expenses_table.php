<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            // Both nullable: an expense can be greenhouse-specific,
            // season-specific, both, or company-wide (Section 16).
            $table->foreignId('greenhouse_id')->nullable()->constrained('greenhouses')->nullOnDelete();
            $table->foreignId('season_id')->nullable()->constrained('seasons')->nullOnDelete();
            $table->foreignId('expense_category_id')->constrained('expense_categories')->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->date('date');
            $table->string('transaction_no')->nullable();
            $table->decimal('amount', 18, 2);
            $table->string('payment_method')->nullable();
            $table->text('description')->nullable();
            $table->string('attachment_path')->nullable();
            // approved_at/approved_by implement the "Expense (approve)"
            // row of the Role & Permission Matrix — Owner/Manager/Finance
            // can approve, Supervisor can only input (create). An expense
            // still counts toward season cost whether approved or not
            // (Aturan #43 — approval is a bookkeeping checkpoint, not a
            // gate on the cost figures being usable); it exists so the
            // business can flag/audit which expenses are reviewed.
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'season_id']);
            $table->index(['company_id', 'greenhouse_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
