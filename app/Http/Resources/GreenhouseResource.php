<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GreenhouseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'location' => $this->location,
            'length' => $this->length,
            'width' => $this->width,
            'area' => $this->area,
            'capacity' => $this->capacity,
            'status' => $this->status,
            'notes' => $this->notes,
            'construction_cost' => $this->constructionCost(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
