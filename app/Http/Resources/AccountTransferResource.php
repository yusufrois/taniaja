<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountTransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'from_account_id' => $this->from_account_id,
            'from_account_name' => $this->whenLoaded('fromAccount', fn () => $this->fromAccount->name),
            'to_account_id' => $this->to_account_id,
            'to_account_name' => $this->whenLoaded('toAccount', fn () => $this->toAccount->name),
            'amount' => $this->amount,
            'date' => $this->date?->toDateString(),
            'notes' => $this->notes,
            'creator_name' => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'created_at' => $this->created_at,
        ];
    }
}
