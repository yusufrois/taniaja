<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'season_id' => $this->season_id,
            'scheduled_date' => $this->scheduled_date?->toDateString(),
            'activity_name' => $this->activity_name,
            'category' => $this->category,
            'instruction' => $this->instruction,
            // 'status' here is the live computed value (pending -> overdue
            // once the date passes), NOT the raw stored column — see
            // Schedule::effectiveStatus().
            'status' => $this->effective_status,
        ];
    }
}
