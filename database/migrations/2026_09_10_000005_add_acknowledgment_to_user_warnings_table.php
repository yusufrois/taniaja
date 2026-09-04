<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Roadmap tambahan — alur "staf tandai sudah baca -> atasan
     * konfirmasi -> baru hilang dari Dashboard". Sengaja 2 tahap
     * (bukan langsung hilang begitu staf klik "Sudah Baca") supaya
     * atasan tahu stafnya benar-benar sudah lihat peringatannya,
     * bukan cuma staf yang bisa "menghilangkan" notifikasi sepihak.
     */
    public function up(): void
    {
        Schema::table('user_warnings', function (Blueprint $table) {
            $table->timestamp('acknowledged_at')->nullable()->after('reason');
            $table->timestamp('confirmed_at')->nullable()->after('acknowledged_at');
            $table->foreignId('confirmed_by')->nullable()->after('confirmed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('user_warnings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('confirmed_by');
            $table->dropColumn(['acknowledged_at', 'confirmed_at']);
        });
    }
};
