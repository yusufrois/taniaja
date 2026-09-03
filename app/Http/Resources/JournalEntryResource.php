<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JournalEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date?->toDateString(),
            'description' => $this->description,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'creator_name' => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'lines' => JournalEntryLineResource::collection($this->whenLoaded('lines')),
            'total_debit' => $this->whenLoaded('lines', fn () => (float) $this->lines->sum('debit')),
            'total_credit' => $this->whenLoaded('lines', fn () => (float) $this->lines->sum('credit')),
            'created_at' => $this->created_at,
        ];
    }
}
