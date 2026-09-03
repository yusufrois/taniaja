<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Buying input stock in bulk (Owner in the "petani + tim" business
     * scheme). ALWAYS creates a linked Expense record (expense_id) —
     * per Aturan #48 (correctness over convenience), a fertilizer
     * purchase that never shows up in Expense/HPP/P&L would silently
     * under-report season cost. See InputPurchaseController::store().
     */
    public function up(): void
    {
        Schema::create('input_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('input_item_id')->constrained('input_items')->restrictOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('expense_category_id')->constrained('expense_categories')->restrictOnDelete();
            $table->foreignId('greenhouse_id')->nullable()->constrained('greenhouses')->nullOnDelete();
            $table->foreignId('season_id')->nullable()->constrained('seasons')->nullOnDelete();
            // The Expense record this purchase auto-created — kept so
            // the two stay traceable to each other in both directions.
            $table->foreignId('expense_id')->nullable()->constrained('expenses')->nullOnDelete();
            $table->date('purchase_date');
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 18, 2);
            $table->decimal('total_amount', 18, 2);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes(); // financial record, Section 30

            $table->index(['company_id', 'input_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('input_purchases');
    }
};
