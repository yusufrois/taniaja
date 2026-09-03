<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DebtResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'greenhouse_id' => $this->greenhouse_id,
            'season_id' => $this->season_id,
            'creditor_name' => $this->creditor_name,
            'debt_date' => $this->debt_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'amount' => $this->amount,
            // Roadmap tambahan — jadwal cicilan otomatis.
            'installment_months' => $this->installment_months,
            'installment_amount' => $this->installment_amount,
            'installments_paid' => $this->installments_paid,
            'total_paid' => $this->total_paid,
            'remaining' => $this->remaining,
            'status' => $this->status,
            'notes' => $this->notes,
            'schedule' => $this->schedule()->values(),
            'payments' => DebtPaymentResource::collection($this->whenLoaded('payments')),
        ];
    }
}
