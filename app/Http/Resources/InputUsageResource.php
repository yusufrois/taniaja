<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InputUsageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $canViewCost = $request->user()->hasPermission('cost.view');

        return [
            'id' => $this->id,
            'input_item_id' => $this->input_item_id,
            'input_item_name' => $this->whenLoaded('inputItem', fn () => $this->inputItem->name),
            'greenhouse_id' => $this->greenhouse_id,
            'season_id' => $this->season_id,
            'activity_id' => $this->activity_id,
            'used_date' => $this->used_date?->toDateString(),
            'quantity' => $this->quantity,
            'cost' => $this->when($canViewCost, $this->cost),
            'expense_id' => $this->when($canViewCost, $this->expense_id),
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
