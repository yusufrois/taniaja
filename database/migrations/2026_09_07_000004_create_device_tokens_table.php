<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FCM (or similar) device tokens for push notifications. Actually
     * SENDING a push requires Firebase project credentials configured
     * separately (see PushNotificationService) — this table just
     * tracks where to send, and works standalone even before that's
     * set up (push simply no-ops, in-app Notification still works).
     */
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('token')->unique();
            $table->string('platform')->nullable(); // android, ios, web
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
