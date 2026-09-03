<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lightweight "quick sale" against a StockBatch — this is what
     * directly answers "beli sekian, jual sekian, untung berapa".
     * It is intentionally simpler than the full Sales/Invoice module
     * (Section 19-21, Phase 7): no customer master data requirement,
     * no invoice PDF, no multi-item cart. When Phase 7 is built, the
     * full Sales module will read/write through this same StockBatch
     * ledger rather than duplicating inventory logic — a proper Sale
     * can reference one or more StockBatchSale rows, or this table can
     * be superseded by sale_items once that exists. Kept separate for
     * now instead of building the full Sales module early, per Aturan
     * #43 (don't build more than what's being asked for right now).
     */
    public function up(): void
    {
        Schema::create('stock_batch_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('stock_batch_id')->constrained('stock_batches')->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->date('sale_date');
            $table->decimal('quantity_sold', 12, 2);
            $table->decimal('sale_price_per_unit', 18, 2);
            $table->decimal('revenue', 18, 2); // quantity_sold * sale_price_per_unit
            $table->decimal('cost', 18, 2); // quantity_sold * batch.unit_cost, snapshotted
            $table->decimal('profit', 18, 2); // revenue - cost, stored so history is stable even if unit_cost logic changes later
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'sale_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_batch_sales');
    }
};
