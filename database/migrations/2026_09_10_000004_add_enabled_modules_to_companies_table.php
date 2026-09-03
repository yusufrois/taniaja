<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Roadmap tambahan — "toggle per modul" untuk perusahaan petani
     * murni vs tengkulak vs campuran, disepakati sebagai pendekatan
     * yang lebih fleksibel daripada memilih "tipe usaha" tetap saat
     * daftar (banyak bisnis pertanian riil menjalankan peran campuran
     * dari waktu ke waktu). NULL/kosong berarti "semua modul aktif"
     * (default aman, tidak mengubah perilaku perusahaan yang sudah
     * ada) — lihat Company::hasModuleEnabled().
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->json('enabled_modules')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('enabled_modules');
        });
    }
};
