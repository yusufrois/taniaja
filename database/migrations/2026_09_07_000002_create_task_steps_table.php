<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "1 tugas bisa punya beberapa sub-langkah" — confirmed in
     * discussion. is_done is tracked PER STEP, independent of the
     * parent Task's own `status` field (the assignee sets Task.status
     * explicitly, steps are a checklist aid, not an auto-derivation
     * source — see TaskController for the reasoning).
     */
    public function up(): void
    {
        Schema::create('task_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->string('description');
            $table->boolean('is_done')->default(false);
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_steps');
    }
};
