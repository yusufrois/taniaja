<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master list for Asset.category — kept as a SEPARATE lookup
     * table populating a dropdown, NOT a foreign key on `assets`
     * itself. Asset.category stays a plain string column (unchanged
     * schema, no migration needed there, no orphan-FK risk if a
     * category gets deleted later) — this table just gives the UI
     * something to pick from instead of free typing.
     */
    public function up(): void
    {
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['company_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_categories');
    }
};
