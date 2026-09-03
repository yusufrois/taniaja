<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\LogsAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSeasonRequest;
use App\Http\Requests\UpdateSeasonRequest;
use App\Http\Resources\SeasonResource;
use App\Models\Season;
use App\Services\SeasonStatusService;

class SeasonController extends Controller
{
    use LogsAudit;

    public function __construct(private SeasonStatusService $seasonStatus) {}

    public function index()
    {
        $this->authorize('viewAny', Season::class);

        // Roadmap Phase 10 — same lazy planning→active promotion the
        // web page does, now also runs for API/Flutter clients.
        $this->seasonStatus->promoteDueSeasons(auth()->user()->company_id);

        return SeasonResource::collection(
            Season::with(['greenhouse', 'crop', 'variety'])
                ->orderByDesc('planting_date')
                ->paginate(20)
        );
    }

    public function store(StoreSeasonRequest $request)
    {
        $this->authorize('create', Season::class);

        $data = $request->validated() + ['status' => $request->input('status', 'planning')];
        $data = $this->seasonStatus->applyHarvestTrigger($data);

        $season = Season::create($data);
        $this->logAudit('create', $season, null, $season->toArray());

        return new SeasonResource($season->load(['greenhouse', 'crop', 'variety']));
    }

    public function show(Season $season)
    {
        $this->authorize('view', $season);

        return new SeasonResource($season->load(['greenhouse', 'crop', 'variety']));
    }

    public function update(UpdateSeasonRequest $request, Season $season)
    {
        $this->authorize('update', $season);

        $old = $season->toArray();
        $data = $this->seasonStatus->applyHarvestTrigger($request->validated());
        $season->update($data);
        $this->logAudit('update', $season, $old, $season->toArray());

        return new SeasonResource($season->load(['greenhouse', 'crop', 'variety']));
    }

    public function destroy(Season $season)
    {
        $this->authorize('delete', $season);

        $season->delete();
        $this->logAudit('delete', $season);

        return response()->json(['message' => 'Season berhasil dihapus.']);
    }

    /**
     * Season Dashboard — architecture doc Section 26.
     *
     * Cost, harvest, and sales totals are wired to 0 for now because the
     * Expense, Harvest, and Sale modules don't exist until Phase 5–7.
     * The keys are already shaped the way the final dashboard will need
     * them so the frontend contract doesn't change later — only the
     * values go from hardcoded 0 to real aggregates as those phases land.
     */
    /**
     * Season Dashboard — architecture doc Section 26.
     *
     * Cost, harvest, and sales totals are still wired to 0 — Expense
     * (Phase 5), Harvest (Phase 6), and Sale (Phase 7) modules don't
     * exist yet. Activity counts are now real, computed from the
     * schedules/activities created in Phase 4.
     */
    /**
     * Season Dashboard — architecture doc Section 26.
     *
     * total_cost now sums real Expense rows tied to this season (Phase 5).
     * It intentionally does NOT include company-wide overhead allocation
     * (Section 5 architecture note) or Activity.cost from ad hoc/completed
     * activities yet — both are Phase 8 report-layer refinements, kept out
     * here to avoid double-counting until that allocation logic exists.
     * Harvest and Sale totals remain 0 until Phase 6–7.
     */
    /**
     * Season Dashboard — architecture doc Section 26.
     *
     * total_harvest_kg now sums real HarvestItem weight (Phase 6).
     * total_sales sums invoiced Sale.total tied to this season (Phase 7)
     * — this does NOT include Phase 6's quick-sell (StockBatchSale)
     * revenue, since a quick sale isn't necessarily tied to any one
     * season (e.g. selling purchased/traded stock). total_cost still
     * excludes company-wide overhead allocation, deferred to Phase 8.
     */
    public function dashboard(Season $season)
    {
        $this->authorize('view', $season);

        $season->load(['greenhouse', 'crop', 'variety']);

        $activitiesTotal = $season->schedules()->count();
        $activitiesCompleted = $season->schedules()->where('status', 'completed')->count();
        $totalCost = $season->expenses()->sum('amount');
        $totalHarvestKg = \App\Models\HarvestItem::whereHas(
            'harvest', fn ($q) => $q->where('season_id', $season->id)
        )->sum('weight');
        $totalSales = $season->sales()->sum('total');

        return response()->json([
            'season' => new SeasonResource($season),
            'summary' => [
                'hst' => $season->hst,
                'plant_count' => $season->plant_count,
                'current_plant_count' => $season->current_plant_count,
                'survival_rate_percent' => $season->survival_rate_percent,
                'target_yield' => $season->target_yield,
                'total_cost' => (float) $totalCost,
                'total_harvest_kg' => (float) $totalHarvestKg,
                'total_sales' => (float) $totalSales,
                'activities_completed' => $activitiesCompleted,
                'activities_total' => $activitiesTotal,
            ],
        ]);
    }
}
