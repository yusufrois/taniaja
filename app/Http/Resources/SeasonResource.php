<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeasonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'season_name' => $this->season_name,
            'greenhouse_id' => $this->greenhouse_id,
            'greenhouse_code' => $this->whenLoaded('greenhouse', fn () => $this->greenhouse->code),
            'crop_id' => $this->crop_id,
            'crop_name' => $this->whenLoaded('crop', fn () => $this->crop->name),
            'variety_id' => $this->variety_id,
            'variety_name' => $this->whenLoaded('variety', fn () => $this->variety->name),
            'planting_date' => $this->planting_date?->toDateString(),
            'estimated_harvest_date' => $this->estimated_harvest_date?->toDateString(),
            'actual_harvest_date' => $this->actual_harvest_date?->toDateString(),
            'plant_count' => $this->plant_count,
            'current_plant_count' => $this->current_plant_count,
            'survival_rate_percent' => $this->survival_rate_percent,
            'target_yield' => $this->target_yield,
            'status' => $this->status,
            'hst' => $this->hst,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
