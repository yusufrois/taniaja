<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tracks plant deaths/losses during a season — Season.plant_count
     * stays as the original planting record (how many were planted),
     * while PlantLoss entries are subtracted from it to get the "how
     * many are actually alive right now" figure (Season::currentPlantCount).
     * Kept as its own table (not folded into Activity) so quantity is a
     * proper number the app can do arithmetic on, not buried in a free
     * text description.
     */
    public function up(): void
    {
        Schema::create('plant_losses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('season_id')->constrained('seasons')->cascadeOnDelete();
            $table->date('date');
            $table->integer('hst_snapshot')->nullable();
            $table->unsignedInteger('quantity');
            $table->string('cause')->nullable(); // free text: penyakit, hama, cuaca, ...
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['season_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plant_losses');
    }
};
