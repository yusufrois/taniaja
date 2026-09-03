<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleItemResource extends JsonResource
{
    /**
     * RBAC roadmap Fase B: `price`/`subtotal` (harga JUAL ke customer)
     * TETAP terlihat untuk semua — itu justru yang harus dilihat User B
     * (gudang) untuk bikin Surat Jalan/invoice. Yang disembunyikan cuma
     * `cost` dan `profit`, karena keduanya membocorkan biaya kulakan/
     * margin, bukan harga jual itu sendiri.
     */
    public function toArray(Request $request): array
    {
        $canViewCost = $request->user()->hasPermission('cost.view');

        return [
            'id' => $this->id,
            'stock_batch_id' => $this->stock_batch_id,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'price' => $this->price,
            'subtotal' => $this->subtotal,
            'cost' => $this->when($canViewCost, $this->cost),
            'profit' => $this->when($canViewCost, $this->profit),
        ];
    }
}
