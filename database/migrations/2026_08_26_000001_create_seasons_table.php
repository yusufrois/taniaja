<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('greenhouse_id')->constrained('greenhouses')->cascadeOnDelete();
            $table->foreignId('crop_id')->constrained('crops')->cascadeOnDelete();
            $table->foreignId('variety_id')->constrained('varieties')->cascadeOnDelete();
            $table->string('season_name');
            $table->date('planting_date');
            $table->date('estimated_harvest_date')->nullable();
            $table->date('actual_harvest_date')->nullable();
            $table->unsignedInteger('plant_count')->nullable();
            $table->decimal('target_yield', 12, 2)->nullable(); // kg
            $table->enum('status', ['planning', 'active', 'harvesting', 'completed', 'cancelled'])
                ->default('planning');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // A greenhouse can only run one non-finished season at a time is a
            // BUSINESS rule (enforced in StoreSeasonRequest), not a DB constraint,
            // because "finished" depends on status, which a unique index can't express.
            $table->index(['company_id', 'greenhouse_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seasons');
    }
};
