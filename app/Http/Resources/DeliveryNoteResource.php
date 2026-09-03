<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'delivery_number' => $this->delivery_number,
            'customer_id' => $this->customer_id,
            'customer_name' => $this->whenLoaded('customer', fn () => $this->customer->name),
            'sale_id' => $this->sale_id,
            'invoice_number' => $this->whenLoaded('sale', fn () => $this->sale?->invoice_number),
            // The whole point of linking sale_id: "sudah lunas atau
            // belum" answers itself via the linked Sale's existing
            // payment_status — no separate tracking needed here.
            'invoice_payment_status' => $this->whenLoaded('sale', fn () => $this->sale?->payment_status),
            'date' => $this->date?->toDateString(),
            'notes' => $this->notes,
            'creator_name' => $this->whenLoaded('creator', fn () => $this->creator?->name),
            'items' => DeliveryNoteItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at,
        ];
    }
}
