<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('season_id')->constrained('seasons')->cascadeOnDelete();
            // Nullable: a schedule generated straight from a template item.
            // If the template item is later edited/deleted, the schedule
            // and its snapshot fields below remain intact (they were
            // copied at generation time, not referenced live).
            $table->foreignId('activity_template_item_id')->nullable()
                ->constrained('activity_template_items')->nullOnDelete();
            $table->date('scheduled_date');
            // Snapshot of the template item at generation time — see note above.
            $table->string('activity_name');
            $table->string('category');
            $table->text('instruction')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed', 'skipped', 'overdue'])
                ->default('pending');
            $table->timestamps();

            $table->index(['season_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
