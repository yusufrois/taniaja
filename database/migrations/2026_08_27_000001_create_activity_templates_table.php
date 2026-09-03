<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            // Bound to Variety, NOT Greenhouse — architecture note #2: one
            // variety's SOP is reusable across every greenhouse that grows it.
            $table->foreignId('variety_id')->constrained('varieties')->cascadeOnDelete();
            $table->string('name'); // e.g. "Template Honey Globe Standar"
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_templates');
    }
};
