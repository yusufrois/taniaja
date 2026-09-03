<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Recording consumption of input stock — the "petugas lapangan
     * mencatat aktivitas pemupukan" side. Nullable link to an existing
     * Activity (Phase 4) so a fertilizing activity log CAN reference
     * exactly what stock it used, without forcing every Activity to
     * go through this table (kept as its own simple endpoint rather
     * than deeply wiring into ActivityController/ScheduleController —
     * see Fase C README for why).
     */
    public function up(): void
    {
        Schema::create('input_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('input_item_id')->constrained('input_items')->restrictOnDelete();
            $table->foreignId('greenhouse_id')->nullable()->constrained('greenhouses')->nullOnDelete();
            $table->foreignId('season_id')->nullable()->constrained('seasons')->nullOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained('activities')->nullOnDelete();
            $table->date('used_date');
            $table->decimal('quantity', 12, 2);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes(); // deleting one should restore stock — see model note

            $table->index(['company_id', 'input_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('input_usages');
    }
};
