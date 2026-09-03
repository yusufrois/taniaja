<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Revision to Fase C: cost is now recognized at USAGE time (which
     * greenhouse actually consumed the fertilizer), not at PURCHASE
     * time (which is often a bulk buy not tied to any one greenhouse).
     * Same "cost realized on consumption, not acquisition" principle
     * already used for StockBatch/SaleItem (Phase 6-7) — see
     * InputUsageController::store() for where the Expense now gets
     * created instead.
     */
    public function up(): void
    {
        Schema::table('input_items', function (Blueprint $table) {
            // Default category used when auto-creating an Expense at
            // usage time — replaces input_purchases.expense_category_id,
            // which no longer needs one per-purchase.
            $table->foreignId('default_expense_category_id')->nullable()
                ->after('category')->constrained('expense_categories')->nullOnDelete();
        });

        Schema::table('input_purchases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('expense_category_id');
            $table->dropConstrainedForeignId('expense_id');
        });

        Schema::table('input_usages', function (Blueprint $table) {
            // Frozen at creation time (quantity × InputItem's weighted-
            // average cost AT THAT MOMENT) — never retroactively
            // recalculated when later purchases change the average,
            // same "unit_cost frozen at first sale" principle from
            // Phase 6.
            $table->decimal('cost', 18, 2)->nullable()->after('quantity');
            $table->foreignId('expense_id')->nullable()
                ->after('activity_id')->constrained('expenses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('input_usages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('expense_id');
            $table->dropColumn('cost');
        });

        Schema::table('input_purchases', function (Blueprint $table) {
            $table->foreignId('expense_category_id')->nullable()
                ->constrained('expense_categories')->nullOnDelete();
            $table->foreignId('expense_id')->nullable()
                ->constrained('expenses')->nullOnDelete();
        });

        Schema::table('input_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_expense_category_id');
        });
    }
};
