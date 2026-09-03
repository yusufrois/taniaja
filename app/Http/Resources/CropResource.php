<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CropResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'notes' => $this->notes,
            'varieties' => VarietyResource::collection($this->whenLoaded('varieties')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
