<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master data for farm inputs consumed by the business itself
     * (fertilizer, pesticide, etc.) — deliberately SEPARATE from
     * StockBatch (Phase 6), which is for goods the business SELLS
     * (own harvest or bought-for-resale). InputItem stock is never
     * sold; it's used up on the farm, per the roadmap's "Stok Input
     * Pertanian" requirement.
     */
    public function up(): void
    {
        Schema::create('input_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name'); // e.g. "Pupuk NPK", "Pestisida X"
            $table->string('unit'); // kg, liter, sak, ...
            $table->string('category')->nullable(); // pupuk, pestisida, ... (free text, not hardcoded)
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('input_items');
    }
};
