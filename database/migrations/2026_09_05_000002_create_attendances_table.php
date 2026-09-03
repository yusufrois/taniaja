<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * status has 4 values (not just hadir/tidak) because Fase H
     * (Payroll) treats them differently: Izin/Sakit do NOT deduct
     * monthly salary, Alpa DOES.
     *
     * GPS (lat/lng) but explicitly NO PHOTO — person's own request:
     * "tanpa foto biar tidak bikin penuh db", used as lightweight
     * proof against checking in from somewhere else entirely.
     *
     * Unique per (company, employee, date) — but see
     * AttendanceController for how mistakes get corrected without
     * fighting this constraint (self check-in upserts; marking someone
     * else gives a clear pointer to the existing record instead of a
     * bare error).
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('date');
            $table->enum('status', ['hadir', 'izin', 'sakit', 'alpa']);
            $table->time('check_in_time')->nullable();
            $table->decimal('check_in_lat', 10, 7)->nullable();
            $table->decimal('check_in_lng', 10, 7)->nullable();
            $table->text('notes')->nullable(); // e.g. reason for izin/sakit
            // Who SUBMITTED this record — the employee themself (self
            // check-in) or whoever marked it on their behalf. Kept for
            // accountability, per the person's own concern about
            // fake/incorrect attendance entries.
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'employee_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
