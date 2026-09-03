<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'season_id' => $this->season_id,
            'schedule_id' => $this->schedule_id,
            'is_ad_hoc' => $this->isAdHoc(),
            'date' => $this->date?->toDateString(),
            'hst_snapshot' => $this->hst_snapshot,
            'category' => $this->category,
            'description' => $this->description,
            'cost' => $this->cost,
            'notes' => $this->notes,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at,
        ];
    }
}
