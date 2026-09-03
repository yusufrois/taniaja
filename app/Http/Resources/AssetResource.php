<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'greenhouse_id' => $this->greenhouse_id,
            'greenhouse_code' => $this->whenLoaded('greenhouse', fn () => $this->greenhouse?->code),
            'name' => $this->name,
            'category' => $this->category,
            'purchase_date' => $this->purchase_date?->toDateString(),
            'value' => $this->value,
            'useful_life_years' => $this->useful_life_years,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
