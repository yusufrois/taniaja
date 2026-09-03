<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDeviceTokenRequest;
use App\Models\DeviceToken;

class DeviceTokenController extends Controller
{
    /**
     * Registers (or re-registers, e.g. after reinstall) the caller's
     * OWN device token — updateOrCreate on the token itself so the
     * same physical device switching accounts doesn't leave stale
     * rows pointing at the wrong user.
     */
    public function store(StoreDeviceTokenRequest $request)
    {
        $deviceToken = DeviceToken::updateOrCreate(
            ['token' => $request->validated('token')],
            ['user_id' => $request->user()->id, 'platform' => $request->validated('platform')]
        );

        return response()->json(['id' => $deviceToken->id, 'message' => 'Device token tersimpan.'], 201);
    }
}
