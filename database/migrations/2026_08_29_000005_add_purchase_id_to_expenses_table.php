<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lets a trading-related operational cost (transport, sorting labor,
     * packaging for goods bought from a supplier) be tied to a specific
     * Purchase — exactly how expenses already link to a Season — so
     * that cost rolls into that Purchase's StockBatch unit cost.
     */
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('purchase_id')->nullable()->after('season_id')
                ->constrained('purchases')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('purchase_id');
        });
    }
};
