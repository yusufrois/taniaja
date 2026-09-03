<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseResource extends JsonResource
{
    /**
     * RBAC roadmap Fase B: harga/biaya kulakan disembunyikan dari user
     * yang tidak punya izin 'cost.view' — mis. petugas gudang (User B)
     * yang cuma perlu tahu ada barang masuk, bukan berapa perusahaan
     * bayar ke petani. Field yang disembunyikan OMITTED dari response
     * (bukan null) via $this->when(), supaya frontend/API konsumen
     * tidak salah tafsir "harga Rp 0" — field itu memang tidak ada.
     */
    public function toArray(Request $request): array
    {
        $canViewCost = $request->user()->hasPermission('cost.view');

        return [
            'id' => $this->id,
            'supplier_id' => $this->supplier_id,
            'supplier_name' => $this->whenLoaded('supplier', fn () => $this->supplier->name),
            'crop_id' => $this->crop_id,
            'variety_id' => $this->variety_id,
            'grade_id' => $this->grade_id,
            'purchase_date' => $this->purchase_date?->toDateString(),
            'quantity' => $this->quantity,
            'unit_price' => $this->when($canViewCost, $this->unit_price),
            'total_amount' => $this->when($canViewCost, $this->total_amount),
            'total_paid' => $this->when($canViewCost, $this->total_paid),
            'remaining' => $this->when($canViewCost, $this->remaining),
            'payment_status' => $this->when($canViewCost, $this->payment_status),
            'stock_batch_id' => $this->whenLoaded('stockBatch', fn () => $this->stockBatch?->id),
            'notes' => $this->notes,
        ];
    }
}
