<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'greenhouse_id' => $this->greenhouse_id,
            'season_id' => $this->season_id,
            'category_id' => $this->expense_category_id,
            'category_name' => $this->whenLoaded('category', fn () => $this->category->name),
            'supplier_id' => $this->supplier_id,
            'supplier_name' => $this->whenLoaded('supplier', fn () => $this->supplier?->name),
            'date' => $this->date?->toDateString(),
            'transaction_no' => $this->transaction_no,
            'amount' => $this->amount,
            'payment_method' => $this->payment_method,
            'description' => $this->description,
            'is_approved' => $this->isApproved(),
            'approved_at' => $this->approved_at,
            'created_at' => $this->created_at,
        ];
    }
}
