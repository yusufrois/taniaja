<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    /**
     * RBAC roadmap Fase B: cuma `total_profit` yang disembunyikan di
     * sini — semua field lain (subtotal/discount/tax/total,
     * total_paid/remaining/payment_status) soal harga JUAL dan status
     * bayar CUSTOMER, bukan biaya/margin, jadi tetap aman dilihat User B.
     */
    public function toArray(Request $request): array
    {
        $canViewCost = $request->user()->hasPermission('cost.view');

        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'date' => $this->date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'customer_id' => $this->customer_id,
            'customer_name' => $this->whenLoaded('customer', fn () => $this->customer->name),
            'greenhouse_id' => $this->greenhouse_id,
            'season_id' => $this->season_id,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'tax' => $this->tax,
            'total' => $this->total,
            'total_paid' => $this->total_paid,
            'remaining' => $this->remaining,
            'payment_status' => $this->payment_status,
            'total_profit' => $this->when($canViewCost, $this->total_profit),
            'items' => SaleItemResource::collection($this->whenLoaded('items')),
            'notes' => $this->notes,
            'created_at' => $this->created_at,
        ];
    }
}
