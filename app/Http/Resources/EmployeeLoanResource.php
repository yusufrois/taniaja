<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeLoanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee_name' => $this->whenLoaded('employee', fn () => $this->employee->name),
            'date' => $this->date?->toDateString(),
            'amount' => $this->amount,
            'remaining_balance' => $this->remainingBalance(),
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
