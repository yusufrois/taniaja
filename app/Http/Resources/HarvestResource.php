<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HarvestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'season_id' => $this->season_id,
            'greenhouse_id' => $this->greenhouse_id,
            'variety_id' => $this->variety_id,
            'harvest_date' => $this->harvest_date?->toDateString(),
            'total_weight' => $this->totalWeight(),
            'items' => HarvestItemResource::collection($this->whenLoaded('items')),
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
