<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlantLossResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'season_id' => $this->season_id,
            'date' => $this->date?->toDateString(),
            'hst_snapshot' => $this->hst_snapshot,
            'quantity' => $this->quantity,
            'cause' => $this->cause,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
