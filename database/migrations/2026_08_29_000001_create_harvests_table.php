<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('harvests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('season_id')->constrained('seasons')->cascadeOnDelete();
            $table->foreignId('greenhouse_id')->constrained('greenhouses')->cascadeOnDelete();
            $table->foreignId('variety_id')->constrained('varieties')->cascadeOnDelete();
            $table->date('harvest_date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['season_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('harvests');
    }
};
