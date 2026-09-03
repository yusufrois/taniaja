<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends push notifications via Firebase Cloud Messaging — deliberately
 * GRACEFUL when not configured: if config('services.fcm.server_key')
 * is empty, this just logs and returns, never throws. That keeps
 * everything else (in-app Notification, the whole Task feature) fully
 * working before a Firebase project is wired up, and safe to run in
 * tests without real credentials.
 *
 * To actually enable push: set FCM_SERVER_KEY in .env (or migrate to
 * Firebase's newer HTTP v1 API with a service account — legacy server
 * key API used here for simplicity; swap sendToToken()'s implementation
 * if you migrate). Reuse the same Firebase project as LWC100's
 * Autodosing push setup if convenient, or a separate TaniAja project.
 */
class PushNotificationService
{
    public function sendToUser(User $user, string $title, string $body, array $data = []): void
    {
        $serverKey = config('services.fcm.server_key');

        if (! $serverKey) {
            Log::info('PushNotificationService: FCM not configured, skipping push.', [
                'user_id' => $user->id, 'title' => $title,
            ]);

            return;
        }

        foreach ($user->deviceTokens as $deviceToken) {
            $this->sendToToken($serverKey, $deviceToken->token, $title, $body, $data);
        }
    }

    private function sendToToken(string $serverKey, string $token, string $title, string $body, array $data): void
    {
        try {
            Http::withHeaders(['Authorization' => "key={$serverKey}"])
                ->post('https://fcm.googleapis.com/fcm/send', [
                    'to' => $token,
                    'notification' => ['title' => $title, 'body' => $body],
                    'data' => $data,
                ]);
        } catch (\Throwable $e) {
            // Never let a push failure break the caller's flow (e.g.
            // Task assignment) — log and move on.
            Log::warning('PushNotificationService: failed to send push.', [
                'token' => $token, 'error' => $e->getMessage(),
            ]);
        }
    }
}
