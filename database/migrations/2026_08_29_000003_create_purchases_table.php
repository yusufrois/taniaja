<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Pembelian dari petani/tengkulak lain" — the trading side of the
     * business, parallel to (and explicitly separate from, per the
     * person's own requirement) own-greenhouse Harvest. Feeds into
     * StockBatch exactly like Harvest does, just with a different
     * cost basis (purchase price + landed cost, instead of season HPP).
     */
    public function up(): void
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('crop_id')->constrained('crops')->restrictOnDelete();
            $table->foreignId('variety_id')->nullable()->constrained('varieties')->nullOnDelete();
            $table->foreignId('grade_id')->nullable()->constrained('grades')->nullOnDelete();
            $table->date('purchase_date');
            $table->decimal('quantity', 12, 2); // kg bought
            $table->decimal('unit_price', 18, 2); // price paid per kg
            $table->decimal('total_amount', 18, 2); // quantity * unit_price, stored (not recomputed) so it survives price/qty edits to history
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes(); // financial record, Section 30

            $table->index(['company_id', 'purchase_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
