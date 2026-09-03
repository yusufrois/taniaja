<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master data for piece-rate (borongan) work — "tarif per JENIS
     * PEKERJAAN" per the person's own words: "Panen Melon" = Rp/kg,
     * "Bersih Gulma" = Rp/m², etc. rate is Rp per 1 unit of `unit`.
     */
    public function up(): void
    {
        Schema::create('work_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('name'); // e.g. "Panen Melon"
            $table->string('unit'); // kg, m2, ikat, ...
            $table->decimal('rate', 18, 2); // Rp per 1 unit
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_types');
    }
};
