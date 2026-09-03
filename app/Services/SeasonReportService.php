<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\HarvestItem;
use App\Models\SaleItem;
use App\Models\Season;
use App\Models\StockBatch;

/**
 * Extracted from ReportController's seasonProfitLoss()/seasonHpp()
 * (Phase 8) so the new "Detail Musim Tanam" web page can show the
 * EXACT same figures as the API, without a second copy of this
 * profit-chain logic drifting out of sync — same Phase 10 principle
 * as AccountingReportService.
 *
 * See ReportController's class docblock for the full explanation of
 * why COGS comes from SaleItem.cost (not re-summing Expense), and why
 * net_profit currently equals gross_profit in this MVP.
 */
class SeasonReportService
{
    public function profitLoss(Season $season): array
    {
        $revenue = (float) $season->sales()->sum('total');
        $cogs = (float) SaleItem::whereHas('sale', fn ($q) => $q->where('season_id', $season->id))->sum('cost');
        $productionCostTotal = (float) $season->expenses()->whereNotNull('approved_at')->sum('amount');
        $unsoldInventoryValue = $this->unsoldInventoryValue($season);
        $grossProfit = round($revenue - $cogs, 2);

        return [
            'season_id' => $season->id,
            'revenue' => $revenue,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'net_profit' => $grossProfit,
            'production_cost_total' => $productionCostTotal,
            'unsold_inventory_value' => $unsoldInventoryValue,
        ];
    }

    public function hpp(Season $season): array
    {
        $totalCost = (float) $season->expenses()->whereNotNull('approved_at')->sum('amount');
        $totalHarvestKg = (float) HarvestItem::whereHas(
            'harvest', fn ($q) => $q->where('season_id', $season->id)
        )->sum('weight');

        return [
            'season_id' => $season->id,
            'total_cost' => $totalCost,
            'total_harvest_kg' => $totalHarvestKg,
            'hpp_per_kg' => $totalHarvestKg > 0 ? round($totalCost / $totalHarvestKg, 2) : null,
        ];
    }

    /** "Rincian pengeluaran dalam 1 musim itu" — the actual expense list, for display, not just the total. */
    public function expenses(Season $season)
    {
        return Expense::where('season_id', $season->id)
            ->with('category')
            ->orderByDesc('date')
            ->get();
    }

    private function unsoldInventoryValue(Season $season): float
    {
        $batches = StockBatch::where('status', 'active')
            ->whereHas('harvestItem.harvest', fn ($q) => $q->where('season_id', $season->id))
            ->get(['quantity_available', 'unit_cost']);

        return (float) $batches->sum(fn ($b) => (float) $b->quantity_available * (float) $b->unit_cost);
    }
}
