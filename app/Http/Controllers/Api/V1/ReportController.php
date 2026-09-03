<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Greenhouse;
use App\Models\HarvestItem;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Schedule;
use App\Models\Season;
use App\Models\StockBatch;
use App\Models\StockBatchSale;
use App\Models\Supplier;
use Illuminate\Http\Request;

/**
 * Read-only reporting endpoints — Section 22-23 (HPP, P&L) and the
 * "aktivitas hari ini/terlambat" business questions from Section 1.
 *
 * Authorization: gated by the 'report.view' permission directly (no
 * Policy class), since reports aren't tied to one Eloquent model the
 * way create/update/delete actions are — there's nothing to pass as
 * the second argument to $this->authorize() here.
 *
 * IMPORTANT — how profit is defined in this MVP (read before changing
 * any formula below): every Expense recorded for a season becomes part
 * of that season's harvested StockBatches' unit_cost (Phase 6). When a
 * batch is sold, SaleItem.cost snapshots exactly how much of that
 * production cost was "used up" by that sale (Phase 7). This means
 * SaleItem.cost is ALREADY a precise, non-overlapping measure of COGS
 * for what's been sold — summing it again against season Expenses
 * would double-count. So:
 *   COGS         = sum(SaleItem.cost) for the relevant sales
 *   Gross Profit = Revenue - COGS
 *   Net Profit   = Gross Profit (this app does not yet distinguish a
 *                  separate "operating expense" category outside of
 *                  production cost — see README for why that split is
 *                  deliberately NOT attempted here)
 * Two purely informational (non-profit-chain) figures are also
 * returned: `production_cost_total` (all Expense for context) and
 * `unsold_inventory_value` (money still sitting in unsold stock) — so
 * the numbers are visible without being wrongly subtracted twice.
 */
class ReportController extends Controller
{
    private function authorizeReportView(Request $request): void
    {
        // abort_unless() is a plain, well-established Laravel helper
        // (already used identically elsewhere in this app, e.g.
        // ScheduleController::complete()) — used here instead of a
        // Gate/Policy call since reports have no single Eloquent model
        // to authorize against.
        abort_unless($request->user()->hasPermission('report.view'), 403, 'Anda tidak punya akses ke laporan ini.');
    }

    public function seasonHpp(Request $request, Season $season, \App\Services\SeasonReportService $seasonReport)
    {
        $this->authorizeReportView($request);

        return response()->json($seasonReport->hpp($season));
    }

    public function seasonProfitLoss(Request $request, Season $season, \App\Services\SeasonReportService $seasonReport)
    {
        $this->authorizeReportView($request);

        return response()->json($seasonReport->profitLoss($season));
    }

    public function greenhousePerformance(Request $request, Greenhouse $greenhouse)
    {
        $this->authorizeReportView($request);

        // A sale can be tied to a greenhouse two ways: directly
        // (sales.greenhouse_id) or indirectly via its season
        // (seasons.greenhouse_id). Both conditions are wrapped in ONE
        // where() closure — critical: an unwrapped ->where()->orWhereHas()
        // combines with the BelongsToCompany global scope as
        // "company_scope AND cond1 OR cond2", which (due to normal
        // AND/OR precedence) would let cond2 escape the tenant scope
        // entirely. Wrapping forces "company_scope AND (cond1 OR cond2)",
        // which is what's actually intended.
        $saleIds = Sale::where(function ($query) use ($greenhouse) {
            $query->where('greenhouse_id', $greenhouse->id)
                ->orWhereHas('season', fn ($q) => $q->where('greenhouse_id', $greenhouse->id));
        })->pluck('id');

        $expenseIds = Expense::whereNotNull('approved_at')->where(function ($query) use ($greenhouse) {
            $query->where('greenhouse_id', $greenhouse->id)
                ->orWhereHas('season', fn ($q) => $q->where('greenhouse_id', $greenhouse->id));
        })->pluck('id');

        $revenue = (float) Sale::whereIn('id', $saleIds)->sum('total');
        $cogs = (float) SaleItem::whereIn('sale_id', $saleIds)->sum('cost');
        $productionCostTotal = (float) Expense::whereIn('id', $expenseIds)->sum('amount');
        $grossProfit = round($revenue - $cogs, 2);
        $constructionCost = $greenhouse->constructionCost();

        return response()->json([
            'greenhouse_id' => $greenhouse->id,
            'revenue' => $revenue,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'net_profit' => $grossProfit,
            'production_cost_total' => $productionCostTotal,
            'construction_cost' => $constructionCost,
            // ROI here is a simple lifetime figure (profit so far ÷ what
            // the greenhouse cost to build), not annualized — a proper
            // annualized/period-based ROI is future report-layer work.
            'roi_percent' => $constructionCost > 0 ? round(($grossProfit / $constructionCost) * 100, 2) : null,
        ]);
    }

    public function companyProfitLoss(Request $request)
    {
        $this->authorizeReportView($request);

        $companyId = $request->user()->company_id;

        return response()->json(
            $this->buildProfitLoss(
                revenue: (float) Sale::where('company_id', $companyId)->sum('total'),
                cogs: (float) SaleItem::where('company_id', $companyId)->sum('cost'),
                productionCostTotal: (float) Expense::where('company_id', $companyId)->whereNotNull('approved_at')->sum('amount'),
                unsoldInventoryValue: $this->unsoldInventoryValueForCompany($companyId),
            )
        );
    }

    /**
     * "Aktivitas apa yang harus dilakukan hari ini / terlambat" —
     * Section 1 Q16-17 and Section 27's notification requirements,
     * reusing Schedule::effective_status (Phase 4) rather than a new
     * calculation, so "overdue" means exactly the same thing here as
     * it does everywhere else in the app.
     */
    public function todayActivities(Request $request)
    {
        $this->authorizeReportView($request);

        $schedules = Schedule::whereIn('status', ['pending', 'in_progress'])
            ->with('season')
            ->get();

        $today = $schedules->filter(fn ($s) => $s->scheduled_date->isToday())->values();
        $overdue = $schedules->filter(fn ($s) => $s->effective_status === 'overdue')->values();

        $map = fn ($s) => [
            'id' => $s->id,
            'season_id' => $s->season_id,
            'season_name' => $s->season?->season_name,
            'scheduled_date' => $s->scheduled_date->toDateString(),
            'activity_name' => $s->activity_name,
            'category' => $s->category,
        ];

        return response()->json([
            'today' => $today->map($map)->values(),
            'overdue' => $overdue->map($map)->values(),
        ]);
    }

    private function buildProfitLoss(
        float $revenue, float $cogs, float $productionCostTotal, float $unsoldInventoryValue, array $extra = []
    ): array {
        $grossProfit = round($revenue - $cogs, 2);

        return $extra + [
            'revenue' => $revenue,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'net_profit' => $grossProfit,
            'production_cost_total' => $productionCostTotal,
            'unsold_inventory_value' => $unsoldInventoryValue,
        ];
    }

    private function unsoldInventoryValueForCompany(int $companyId): float
    {
        $batches = StockBatch::where('company_id', $companyId)
            ->where('status', 'active')
            ->get(['quantity_available', 'unit_cost']);

        return (float) $batches->sum(fn ($b) => (float) $b->quantity_available * (float) $b->unit_cost);
    }

    /**
     * Roadmap Fase J — "petani panen berapa dan dijual kesiapa"
     * (Season → Harvest → StockBatch → whoever bought it, tracing
     * through BOTH sale paths: full invoices (SaleItem) and the
     * quick-sale flow (StockBatchSale), since a company may use either
     * or both). All data already existed since Phase 6/7 — this is
     * purely a read-only aggregation, no new source-of-truth data.
     */
    public function seasonTraceability(Request $request, Season $season)
    {
        $this->authorizeReportView($request);

        $canViewCost = $request->user()->hasPermission('cost.view');

        $harvestItems = HarvestItem::whereHas('harvest', fn ($q) => $q->where('season_id', $season->id))
            ->with(['grade', 'stockBatch'])
            ->get();

        $totalHarvested = (float) $harvestItems->sum('weight');

        $stockBatches = $harvestItems->pluck('stockBatch')->filter()->values();

        $batchData = $stockBatches->map(function (StockBatch $batch) use ($canViewCost) {
            $soldTo = collect();

            SaleItem::where('stock_batch_id', $batch->id)
                ->with('sale.customer')
                ->get()
                ->each(function (SaleItem $item) use ($soldTo, $canViewCost) {
                    $soldTo->push([
                        'customer_name' => $item->sale?->customer?->name,
                        'quantity' => (float) $item->quantity,
                        'date' => $item->sale?->date?->toDateString(),
                        'via' => 'invoice',
                        'revenue' => $canViewCost ? (float) $item->subtotal : null,
                    ]);
                });

            StockBatchSale::where('stock_batch_id', $batch->id)
                ->with('customer')
                ->get()
                ->each(function (StockBatchSale $qs) use ($soldTo, $canViewCost) {
                    $soldTo->push([
                        'customer_name' => $qs->customer?->name,
                        'quantity' => (float) $qs->quantity_sold,
                        'date' => $qs->sale_date?->toDateString(),
                        'via' => 'quick_sale',
                        'revenue' => $canViewCost ? (float) $qs->revenue : null,
                    ]);
                });

            return [
                'stock_batch_id' => $batch->id,
                'grade' => $batch->grade?->name,
                'quantity_acquired' => (float) $batch->quantity_acquired,
                'quantity_available' => (float) $batch->quantity_available,
                'sold_to' => $soldTo->values(),
            ];
        });

        return response()->json([
            'season' => [
                'id' => $season->id,
                'season_name' => $season->season_name,
            ],
            'total_harvested' => $totalHarvested,
            'total_sold' => round($batchData->sum(fn ($b) => $b['sold_to']->sum('quantity')), 2),
            'total_remaining_stock' => round((float) $stockBatches->sum('quantity_available'), 2),
            'stock_batches' => $batchData->values(),
        ]);
    }

    /**
     * Roadmap Fase K — riwayat pembelian per petani/tengkulak sumber:
     * tanggal, jumlah, harga (kalau cost.view), staff yang beli, status
     * lunas/hutang. Data dari Purchase (Phase 6), dikelompokkan per
     * Supplier — bukan tabel/data baru.
     */
    public function supplierHistory(Request $request, Supplier $supplier)
    {
        $this->authorizeReportView($request);

        $canViewCost = $request->user()->hasPermission('cost.view');

        $purchases = Purchase::where('supplier_id', $supplier->id)
            ->with(['crop', 'variety', 'creator'])
            ->orderByDesc('purchase_date')
            ->get();

        $data = $purchases->map(fn (Purchase $p) => [
            'id' => $p->id,
            'purchase_date' => $p->purchase_date?->toDateString(),
            'crop' => $p->crop?->name,
            'variety' => $p->variety?->name,
            'quantity' => (float) $p->quantity,
            'unit_price' => $canViewCost ? (float) $p->unit_price : null,
            'total_amount' => $canViewCost ? (float) $p->total_amount : null,
            'payment_status' => $canViewCost ? $p->payment_status : null,
            'purchased_by' => $p->creator?->name,
        ]);

        return response()->json([
            'supplier' => ['id' => $supplier->id, 'name' => $supplier->name],
            'total_purchases' => $purchases->count(),
            'total_quantity' => round((float) $purchases->sum('quantity'), 2),
            'total_amount' => $canViewCost ? round((float) $purchases->sum('total_amount'), 2) : null,
            'purchases' => $data->values(),
        ]);
    }
}
