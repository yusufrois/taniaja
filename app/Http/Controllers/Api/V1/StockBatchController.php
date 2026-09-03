<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\LogsAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStockBatchSaleRequest;
use App\Http\Resources\StockBatchResource;
use App\Http\Resources\StockBatchSaleResource;
use App\Models\StockBatch;
use App\Services\Stock\StockBatchService;

class StockBatchController extends Controller
{
    use LogsAudit;

    /**
     * GET /stock-batches — the unified inventory view: own-harvest and
     * purchased stock side by side, filterable by source_type so the
     * person can see "hasil kebun sendiri" vs "barang dagangan" clearly,
     * per their explicit requirement that the two stay distinguishable.
     */
    public function index()
    {
        $this->authorize('viewAny', \App\Models\StockBatchSale::class);

        $query = StockBatch::with(['crop', 'variety', 'grade'])->where('status', 'active');

        if (request('source_type')) {
            $query->where('source_type', request('source_type'));
        }

        return StockBatchResource::collection($query->orderByDesc('acquired_date')->paginate(20));
    }

    /**
     * POST /stock-batches/{batch}/sell — the direct answer to "beli
     * sekian, jual sekian, untung berapa". Works identically whether
     * the batch came from Harvest or Purchase; the caller doesn't need
     * to know or care which.
     */
    public function sell(StoreStockBatchSaleRequest $request, StockBatch $batch, StockBatchService $stockBatchService)
    {
        $this->authorize('create', \App\Models\StockBatchSale::class);

        // StockBatchService::sell() throws ValidationException itself
        // (e.g. selling more than quantity_available) — Laravel renders
        // that as a normal 422 automatically, no try/catch needed here.
        $sale = $stockBatchService->sell(
            $batch,
            (float) $request->validated('quantity_sold'),
            (float) $request->validated('sale_price_per_unit'),
            $request->validated('customer_id'),
            $request->validated('notes'),
            $request->user()->id,
        );

        $this->logAudit('stock_batch.sell', $batch, null, $sale->toArray());

        return new StockBatchSaleResource($sale);
    }
}
