<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('sale_id')->constrained('sales')->cascadeOnDelete();
            // Nullable: a line item CAN be tied to inventory (draws down
            // a StockBatch, same ledger Phase 6 uses) so COGS/profit is
            // trackable — or left null for something not stock-tracked.
            $table->foreignId('stock_batch_id')->nullable()->constrained('stock_batches')->nullOnDelete();
            $table->string('description'); // product name, snapshotted at sale time
            $table->decimal('quantity', 12, 2);
            $table->string('unit')->nullable();
            $table->decimal('price', 18, 2); // price per unit
            $table->decimal('subtotal', 18, 2); // quantity * price, stored not recomputed
            $table->decimal('cost', 18, 2)->default(0); // quantity * stock_batch.unit_cost at sale time, snapshotted
            $table->decimal('profit', 18, 2)->default(0); // subtotal - cost, snapshotted
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};
