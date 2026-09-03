<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalEntryLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'chart_of_account_id' => $this->chart_of_account_id,
            'account_code' => $this->whenLoaded('chartOfAccount', fn () => $this->chartOfAccount->code),
            'account_name' => $this->whenLoaded('chartOfAccount', fn () => $this->chartOfAccount->name),
            'debit' => $this->debit,
            'credit' => $this->credit,
            'notes' => $this->notes,
        ];
    }
}
