<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityTemplateItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hst' => $this->hst,
            'activity_name' => $this->activity_name,
            'category' => $this->category,
            'description' => $this->description,
            'material' => $this->material,
            'dosage' => $this->dosage,
            'unit' => $this->unit,
            'instruction' => $this->instruction,
            'estimated_duration_minutes' => $this->estimated_duration_minutes,
        ];
    }
}
