<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('activity_template_id')->constrained('activity_templates')->cascadeOnDelete();
            $table->integer('hst'); // day offset from planting_date; can be 0
            $table->string('activity_name');
            $table->string('category'); // pemupukan, penyemprotan, penyiraman, pruning, ...
            $table->text('description')->nullable();
            $table->string('material')->nullable();
            $table->string('dosage')->nullable();
            $table->string('unit')->nullable();
            $table->text('instruction')->nullable();
            $table->unsignedInteger('estimated_duration_minutes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['activity_template_id', 'hst']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_template_items');
    }
};
