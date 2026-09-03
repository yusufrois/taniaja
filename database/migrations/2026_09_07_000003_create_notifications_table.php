<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * In-app notification record — the RELIABLE half of "dalam
     * aplikasi DAN push notification" (confirmed in discussion). This
     * table is always written; push (see device_tokens/PushNotificationService)
     * is best-effort on top of it, never the only record of a notification.
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type'); // e.g. "task_assigned"
            $table->string('title');
            $table->text('body')->nullable();
            $table->json('data')->nullable(); // e.g. {"task_id": 5}
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
