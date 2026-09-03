<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InputPurchaseResource extends JsonResource
{
    /**
     * unit_price/total_amount gated behind 'cost.view' (Fase B) — a
     * purchase record inherently reveals what was paid, same as
     * PurchaseResource for trading goods.
     */
    public function toArray(Request $request): array
    {
        $canViewCost = $request->user()->hasPermission('cost.view');

        return [
            'id' => $this->id,
            'input_item_id' => $this->input_item_id,
            'input_item_name' => $this->whenLoaded('inputItem', fn () => $this->inputItem->name),
            'supplier_id' => $this->supplier_id,
            'supplier_name' => $this->whenLoaded('supplier', fn () => $this->supplier?->name),
            'greenhouse_id' => $this->greenhouse_id,
            'season_id' => $this->season_id,
            'purchase_date' => $this->purchase_date?->toDateString(),
            'quantity' => $this->quantity,
            'unit_price' => $this->when($canViewCost, $this->unit_price),
            'total_amount' => $this->when($canViewCost, $this->total_amount),
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
