<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Self-referencing FK: which user is this user's direct supervisor/
     * manager. Nullable — Owner and any user without an assigned
     * supervisor simply has none. This is what "Manager bisa lihat data
     * semua timnya" / "SPV bisa lihat bawahannya" is built on: a user's
     * "team" = themselves + everyone whose supervisor_id points to them
     * (direct reports only, not a deep multi-level hierarchy — kept
     * simple to match how small agribusiness teams are actually
     * structured, per Aturan #43).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('supervisor_id')->nullable()->after('company_id')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supervisor_id');
        });
    }
};
