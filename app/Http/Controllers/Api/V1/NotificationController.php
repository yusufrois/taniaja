<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;

/**
 * Always self-only — no permission catalog entry needed. A
 * notification is inherently personal; there's no scenario where
 * someone should see another user's notification list.
 */
class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->notifications()->paginate(20);

        return NotificationResource::collection($notifications);
    }

    public function markRead(Notification $notification)
    {
        abort_if($notification->user_id !== auth()->id(), 404);

        $notification->update(['read_at' => now()]);

        return new NotificationResource($notification);
    }

    /**
     * Roadmap tambahan — "notifikasi bisa dihapus, karena kalau
     * tidak akan semakin banyak". Self-only, same as every other
     * action here — a notification is never anyone else's to delete.
     */
    public function destroy(Notification $notification)
    {
        abort_if($notification->user_id !== auth()->id(), 404);

        $notification->delete();

        return response()->json(['message' => 'Notifikasi dihapus.']);
    }

    /** Bulk "bersihkan yang sudah dibaca" — the actual fix for "semakin banyak" if left unchecked. */
    public function clearRead()
    {
        $count = auth()->user()->notifications()->whereNotNull('read_at')->delete();

        return response()->json(['message' => "{$count} notifikasi yang sudah dibaca telah dihapus."]);
    }
}
