<?php

namespace App\Services\Stock;

use App\Models\Harvest;
use App\Models\HarvestItem;
use App\Models\Purchase;
use App\Models\StockBatch;
use App\Models\StockBatchSale;
use Illuminate\Validation\ValidationException;

/**
 * Central place where StockBatch rows are created and cost-per-unit is
 * computed — kept as one service (not scattered across controllers) so
 * "how much does a kg in this batch actually cost" has exactly one
 * definition, per Aturan #48 (avoid data that can drift out of sync).
 */
class StockBatchService
{
    /**
     * Own-harvest batch cost: this season's cumulative expenses so far,
     * divided across ALL harvest weight recorded so far for the season
     * (not just this one HarvestItem) — a melon harvested today shares
     * the season's fertilizer cost with melons harvested last week.
     * This is an APPROXIMATION of the final Section 22 HPP calculation
     * (which Phase 8's reporting layer will compute more precisely,
     * potentially with company-wide overhead allocation) — good enough
     * to price a batch for sale today without waiting for the season
     * to close.
     */
    public function createFromHarvestItem(HarvestItem $item): StockBatch
    {
        /** @var Harvest $harvest */
        $harvest = $item->harvest;
        $season = $harvest->season;

        $seasonTotalCost = (float) $season->expenses()->sum('amount');
        $seasonTotalWeightSoFar = (float) \App\Models\HarvestItem::whereHas(
            'harvest', fn ($q) => $q->where('season_id', $season->id)
        )->sum('weight');

        $unitCost = $seasonTotalWeightSoFar > 0
            ? $seasonTotalCost / $seasonTotalWeightSoFar
            : 0.0;

        return StockBatch::create([
            'company_id' => $item->company_id,
            'source_type' => 'own_harvest',
            'harvest_item_id' => $item->id,
            'crop_id' => $season->crop_id,
            'variety_id' => $season->variety_id,
            'grade_id' => $item->grade_id,
            'acquired_date' => $harvest->harvest_date,
            'quantity_acquired' => $item->weight,
            'quantity_available' => $item->weight,
            'unit_cost' => round($unitCost, 2),
            'status' => 'active',
        ]);
    }

    /**
     * Purchased batch cost: purchase price per kg PLUS any trading
     * operational expenses linked to this purchase (transport, sorting
     * labor — Expense.purchase_id), spread across the purchased quantity.
     */
    public function createFromPurchase(Purchase $purchase): StockBatch
    {
        $landedCost = (float) $purchase->landedCostExpenses()->sum('amount');
        $unitCost = $purchase->quantity > 0
            ? ((float) $purchase->total_amount + $landedCost) / (float) $purchase->quantity
            : 0.0;

        return StockBatch::create([
            'company_id' => $purchase->company_id,
            'source_type' => 'purchased',
            'purchase_id' => $purchase->id,
            'crop_id' => $purchase->crop_id,
            'variety_id' => $purchase->variety_id,
            'grade_id' => $purchase->grade_id,
            'acquired_date' => $purchase->purchase_date,
            'quantity_acquired' => $purchase->quantity,
            'quantity_available' => $purchase->quantity,
            'unit_cost' => round($unitCost, 2),
            'status' => 'active',
        ]);
    }

    /**
     * Re-runs the same cost formula as createFromPurchase() whenever a
     * new landed-cost Expense is linked to a Purchase AFTER its batch
     * already exists (the normal flow: buy first, then log transport/
     * sorting costs as they come in). Only applies if nothing has been
     * sold from the batch yet — once a sale has happened, the cost
     * basis is frozen (see the migration note on stock_batches.unit_cost)
     * so historical profit figures don't shift under a completed sale.
     */
    public function recalculateForPurchase(Purchase $purchase): void
    {
        $batch = $purchase->stockBatch;

        if (! $batch || $batch->quantity_available != $batch->quantity_acquired) {
            return;
        }

        $landedCost = (float) $purchase->landedCostExpenses()->sum('amount');
        $unitCost = $purchase->quantity > 0
            ? ((float) $purchase->total_amount + $landedCost) / (float) $purchase->quantity
            : 0.0;

        $batch->update(['unit_cost' => round($unitCost, 2)]);
    }

    /**
     * Records a sale against a batch and computes profit in the same
     * step — this is the concrete answer to "beli sekian, jual sekian,
     * untung berapa". cost/profit are snapshotted onto the sale row so
     * historical figures never shift if unit_cost logic changes later.
     */
    public function sell(StockBatch $batch, float $quantity, float $pricePerUnit, ?int $customerId, ?string $notes, ?int $userId): StockBatchSale
    {
        if ($quantity > (float) $batch->quantity_available) {
            throw ValidationException::withMessages([
                'quantity_sold' => 'Jumlah yang dijual ('.$quantity.' kg) melebihi stok tersedia ('.
                    $batch->quantity_available.' kg) di batch ini.',
            ]);
        }

        $revenue = round($quantity * $pricePerUnit, 2);
        $cost = round($quantity * (float) $batch->unit_cost, 2);
        $profit = round($revenue - $cost, 2);

        $sale = StockBatchSale::create([
            'company_id' => $batch->company_id,
            'stock_batch_id' => $batch->id,
            'customer_id' => $customerId,
            'sale_date' => now()->toDateString(),
            'quantity_sold' => $quantity,
            'sale_price_per_unit' => $pricePerUnit,
            'revenue' => $revenue,
            'cost' => $cost,
            'profit' => $profit,
            'notes' => $notes,
            'created_by' => $userId,
        ]);

        $newAvailable = (float) $batch->quantity_available - $quantity;
        $batch->update([
            'quantity_available' => $newAvailable,
            'status' => $newAvailable <= 0 ? 'depleted' : 'active',
        ]);

        return $sale;
    }
}
