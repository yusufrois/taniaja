<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChartOfAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $canViewCost = $request->user()->hasPermission('cost.view');

        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'is_active' => $this->is_active,
            // Balance reveals financial position — gated behind
            // cost.view same as everywhere else money-related in this
            // app (Fase B).
            'balance' => $this->when($canViewCost, fn () => $this->balance()),
        ];
    }
}
