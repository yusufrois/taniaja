<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserWarningResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'issued_by' => $this->issued_by,
            'issuer_name' => $this->whenLoaded('issuer', fn () => $this->issuer?->name),
            'reason' => $this->reason,
            'created_at' => $this->created_at,
        ];
    }
}
