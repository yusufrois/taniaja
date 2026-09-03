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

    /**
     * Response is explicitly wrapped in ['data' => ...] here — after
     * extensive diagnosis (confirmed via raw response dump) this
     * specific action's response was NOT being auto-wrapped in "data"
     * the way every other single-JsonResource controller return in
     * this app is, for a reason not fully pinned down. Wrapping
     * explicitly removes the ambiguity entirely and matches what every
     * other endpoint's response shape looks like.
     */
    public function markRead(Notification $notification)
    {
        abort_if($notification->user_id !== auth()->id(), 404);

        $notification->update(['read_at' => now()]);

        return response()->json(['data' => new NotificationResource($notification->fresh())]);
    }
}
