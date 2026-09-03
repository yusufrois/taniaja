<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('varieties', function (Blueprint $table) {
            $table->id();
            // company_id denormalized here too, so BelongsToCompany's global
            // scope can filter directly without an extra join through crops.
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('crop_id')->constrained('crops')->cascadeOnDelete();
            $table->string('name');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['crop_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('varieties');
    }
};
