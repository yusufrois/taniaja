<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Deliberately SEPARATE from Schedule (Phase 4, cultivation
     * activities generated from crop-cycle templates) — confirmed in
     * discussion: this is for direct/ad-hoc orders ("benerin pompa
     * air"), not the recurring farming calendar.
     *
     * assigned_to/assigned_by are Users (not Employee like Attendance)
     * — a Task needs someone who can actually see/checklist it in the
     * app, unlike Attendance which explicitly supports people with no
     * account at all.
     *
     * related_task_id: "tugas susulan" (follow-up) is confirmed to be
     * a NEW, separate Task marked related to the old one — not steps
     * appended to the original.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('assigned_to')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->foreignId('related_task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'assigned_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
