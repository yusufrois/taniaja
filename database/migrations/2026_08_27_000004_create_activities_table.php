<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('season_id')->constrained('seasons')->cascadeOnDelete();
            // NULL = Ad Hoc Activity (Section 11). NOT NULL = this activity
            // is the completion record of that Scheduled Activity.
            $table->foreignId('schedule_id')->nullable()->constrained('schedules')->nullOnDelete();
            $table->date('date');
            $table->integer('hst_snapshot')->nullable();
            $table->string('category');
            $table->text('description')->nullable();
            $table->decimal('cost', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['season_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
