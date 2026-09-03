<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * pay_type decides which payroll calculation applies (see
     * PayrollCalculationService): monthly_salary only matters for
     * 'monthly', daily_rate only for 'daily' — a 'piece_rate' employee
     * uses neither (their pay comes from PieceWorkLog × WorkType.rate
     * instead), so both stay nullable rather than forcing 0.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->enum('pay_type', ['monthly', 'daily', 'piece_rate'])->nullable()->after('position');
            $table->decimal('monthly_salary', 18, 2)->nullable()->after('pay_type');
            $table->decimal('daily_rate', 18, 2)->nullable()->after('monthly_salary');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['pay_type', 'monthly_salary', 'daily_rate']);
        });
    }
};
