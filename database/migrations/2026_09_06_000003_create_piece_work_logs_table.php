<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One entry = one employee's work result for one day/job. `rate`
     * and `amount` are FROZEN at entry time (same "unit_cost frozen"
     * principle as StockBatch/InputUsage) — a later change to
     * WorkType.rate must never retroactively change what an already-
     * logged day's work was worth.
     */
    public function up(): void
    {
        Schema::create('piece_work_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('work_type_id')->constrained('work_types')->restrictOnDelete();
            $table->date('date');
            $table->decimal('quantity', 12, 2); // e.g. kg picked, m2 cleared
            $table->decimal('rate', 18, 2); // frozen copy of WorkType.rate at entry time
            $table->decimal('amount', 18, 2); // frozen: quantity * rate
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'employee_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('piece_work_logs');
    }
};
