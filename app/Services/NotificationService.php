<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

/**
 * The single entry point for "notify this user" — always writes the
 * reliable in-app Notification row, then best-effort attempts push
 * (see PushNotificationService). Callers (TaskController etc.) should
 * never touch Notification/PushNotificationService directly — go
 * through this so the "always in-app, push is best-effort on top"
 * guarantee holds everywhere.
 */
class NotificationService
{
    public function __construct(private PushNotificationService $push) {}

    public function notify(User $user, string $type, string $title, string $body, array $data = []): Notification
    {
        $notification = Notification::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);

        $this->push->sendToUser($user, $title, $body, $data);

        return $notification;
    }
}
