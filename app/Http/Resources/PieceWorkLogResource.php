<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PieceWorkLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee_name' => $this->whenLoaded('employee', fn () => $this->employee->name),
            'work_type_id' => $this->work_type_id,
            'work_type_name' => $this->whenLoaded('workType', fn () => $this->workType->name),
            'date' => $this->date?->toDateString(),
            'quantity' => $this->quantity,
            'rate' => $this->rate,
            'amount' => $this->amount,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
