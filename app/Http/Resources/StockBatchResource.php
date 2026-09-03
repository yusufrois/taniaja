<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockBatchResource extends JsonResource
{
    /**
     * RBAC roadmap Fase B: unit_cost disembunyikan dari user tanpa
     * izin 'cost.view' — berlaku untuk batch dari SUMBER MANAPUN
     * (own_harvest maupun purchased), karena unit_cost sama-sama
     * membocorkan biaya produksi/beli, hanya jalurnya beda.
     */
    public function toArray(Request $request): array
    {
        $canViewCost = $request->user()->hasPermission('cost.view');

        return [
            'id' => $this->id,
            'source_type' => $this->source_type,
            'crop_id' => $this->crop_id,
            'variety_id' => $this->variety_id,
            'grade_id' => $this->grade_id,
            'acquired_date' => $this->acquired_date?->toDateString(),
            'quantity_acquired' => $this->quantity_acquired,
            'quantity_available' => $this->quantity_available,
            'unit_cost' => $this->when($canViewCost, $this->unit_cost),
            'status' => $this->status,
        ];
    }
}
