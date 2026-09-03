<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One per employee per PayrollPeriod. `detail` (JSON) holds the
     * calculation breakdown (e.g. hadir/alpa day counts and the daily
     * rate used, or the list of piece-work entries summed) — kept so a
     * payslip is self-explanatory later even if Attendance/WorkType
     * data changes afterward.
     */
    public function up(): void
    {
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('payroll_period_id')->constrained('payroll_periods')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->enum('pay_type', ['monthly', 'daily', 'piece_rate']); // snapshot, in case Employee's changes later
            $table->decimal('gross_amount', 18, 2);
            $table->decimal('deduction_amount', 18, 2)->default(0); // kasbon deducted this period
            $table->decimal('net_amount', 18, 2);
            $table->json('detail')->nullable();
            $table->timestamps();

            $table->unique(['payroll_period_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
