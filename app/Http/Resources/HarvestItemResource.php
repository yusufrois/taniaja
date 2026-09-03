<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HarvestItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'grade_id' => $this->grade_id,
            'grade_name' => $this->whenLoaded('grade', fn () => $this->grade->name),
            'quantity' => $this->quantity,
            'weight' => $this->weight,
            'quality_notes' => $this->quality_notes,
            'stock_batch_id' => $this->whenLoaded('stockBatch', fn () => $this->stockBatch?->id),
        ];
    }
}
