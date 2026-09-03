<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('harvest_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('harvest_id')->constrained('harvests')->cascadeOnDelete();
            $table->foreignId('grade_id')->constrained('grades')->restrictOnDelete();
            $table->decimal('quantity', 12, 2)->nullable(); // count, e.g. number of melons
            $table->decimal('weight', 12, 2); // kg — the primary figure HPP is based on
            $table->text('quality_notes')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();

            $table->index(['harvest_id', 'grade_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('harvest_items');
    }
};
