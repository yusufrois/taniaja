<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Unified sellable inventory ledger. Every Harvest item AND every
     * Purchase creates exactly one StockBatch row here — this is the
     * single place Sales (this phase's quick-sale flow, and the full
     * Sales/Invoice module in Phase 7) reads from to know what's
     * available to sell and at what cost, regardless of origin.
     *
     * source_type + source_id is a lightweight polymorphic reference
     * (not Eloquent morphs, since there are only ever these two source
     * types — a plain enum + nullable FKs is simpler and still lets the
     * DB enforce referential integrity on whichever one is set).
     */
    public function up(): void
    {
        Schema::create('stock_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->enum('source_type', ['own_harvest', 'purchased']);
            $table->foreignId('harvest_item_id')->nullable()->constrained('harvest_items')->cascadeOnDelete();
            $table->foreignId('purchase_id')->nullable()->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('crop_id')->constrained('crops')->restrictOnDelete();
            $table->foreignId('variety_id')->nullable()->constrained('varieties')->nullOnDelete();
            $table->foreignId('grade_id')->nullable()->constrained('grades')->nullOnDelete();
            $table->date('acquired_date');
            $table->decimal('quantity_acquired', 12, 2); // kg, original amount
            $table->decimal('quantity_available', 12, 2); // kg, decremented as sales happen
            // Cost per kg, computed ONCE at batch creation and frozen —
            // see StockBatchCostCalculator. Later expenses linked to the
            // same season/purchase do NOT retroactively change a batch
            // that's already been (partially) sold, matching how real
            // bookkeeping treats a cost basis as fixed at time of sale.
            $table->decimal('unit_cost', 18, 2);
            $table->enum('status', ['active', 'depleted'])->default('active');
            $table->timestamps();

            $table->index(['company_id', 'source_type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_batches');
    }
};
