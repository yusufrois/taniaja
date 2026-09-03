<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockBatchSaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'stock_batch_id' => $this->stock_batch_id,
            'customer_id' => $this->customer_id,
            'sale_date' => $this->sale_date?->toDateString(),
            'quantity_sold' => $this->quantity_sold,
            'sale_price_per_unit' => $this->sale_price_per_unit,
            'revenue' => $this->revenue,
            'cost' => $this->cost,
            'profit' => $this->profit,
            'notes' => $this->notes,
        ];
    }
}
