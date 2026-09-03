<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InputItemResource extends JsonResource
{
    /**
     * average_unit_cost gated behind 'cost.view' (Fase B) — same
     * reasoning as Purchase.unit_price: reveals what the company pays,
     * which a warehouse/field clerk shouldn't necessarily see.
     * current_stock stays visible to everyone who can view the item at
     * all — knowing HOW MUCH is left isn't a cost/margin concern.
     */
    public function toArray(Request $request): array
    {
        $canViewCost = $request->user()->hasPermission('cost.view');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'unit' => $this->unit,
            'category' => $this->category,
            'default_expense_category_id' => $this->default_expense_category_id,
            'current_stock' => $this->currentStock(),
            'average_unit_cost' => $this->when($canViewCost, fn () => $this->averageUnitCost()),
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
