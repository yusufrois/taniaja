<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            // Nullable: company-wide assets (e.g. a truck) aren't tied to
            // one greenhouse. When set, category = 'greenhouse_construction'
            // is what Greenhouse::constructionCost() sums (Phase 1 note #3).
            $table->foreignId('greenhouse_id')->nullable()->constrained('greenhouses')->nullOnDelete();
            $table->string('name');
            $table->string('category'); // greenhouse_construction, pump, irrigation, electrical, tank, machine, tool, ...
            $table->date('purchase_date');
            $table->decimal('value', 18, 2);
            $table->unsignedInteger('useful_life_years')->nullable();
            $table->enum('status', ['active', 'disposed', 'under_maintenance'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'greenhouse_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
