<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'has_account' => $this->user_id !== null,
            'name' => $this->name,
            'phone' => $this->phone,
            'position' => $this->position,
            'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
