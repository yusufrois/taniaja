<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\LogsAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHarvestRequest;
use App\Http\Resources\HarvestResource;
use App\Models\Harvest;
use App\Services\Stock\StockBatchService;
use Illuminate\Support\Facades\DB;

class HarvestController extends Controller
{
    use LogsAudit;

    public function index()
    {
        $this->authorize('viewAny', Harvest::class);

        return HarvestResource::collection(
            Harvest::with('items.grade')->orderByDesc('harvest_date')->paginate(20)
        );
    }

    /**
     * One request creates the Harvest + all its HarvestItems (one per
     * grade, Section 17-18) AND a StockBatch per item in a single DB
     * transaction — so a harvest never exists without becoming sellable
     * inventory, and vice versa.
     */
    public function store(StoreHarvestRequest $request, StockBatchService $stockBatchService)
    {
        $this->authorize('create', Harvest::class);

        $validated = $request->validated();
        $season = \App\Models\Season::findOrFail($validated['season_id']);

        $harvest = DB::transaction(function () use ($validated, $season, $stockBatchService, $request) {
            $harvest = Harvest::create([
                'company_id' => $request->user()->company_id,
                'season_id' => $season->id,
                'greenhouse_id' => $season->greenhouse_id,
                'variety_id' => $season->variety_id,
                'harvest_date' => $validated['harvest_date'],
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            foreach ($validated['items'] as $itemData) {
                $item = $harvest->items()->create([
                    'company_id' => $harvest->company_id,
                    'grade_id' => $itemData['grade_id'],
                    'quantity' => $itemData['quantity'] ?? null,
                    'weight' => $itemData['weight'],
                    'quality_notes' => $itemData['quality_notes'] ?? null,
                ]);

                $stockBatchService->createFromHarvestItem($item);
            }

            return $harvest;
        });

        $this->logAudit('create', $harvest, null, $harvest->toArray());

        return new HarvestResource($harvest->load('items.grade'));
    }

    public function show(Harvest $harvest)
    {
        $this->authorize('view', $harvest);

        return new HarvestResource($harvest->load('items.grade', 'items.stockBatch'));
    }

    public function destroy(Harvest $harvest)
    {
        $this->authorize('delete', $harvest);

        $harvest->delete();
        $this->logAudit('delete', $harvest);

        return response()->json(['message' => 'Panen berhasil dihapus.']);
    }
}
