<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\LogsAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlantLossRequest;
use App\Http\Resources\PlantLossResource;
use App\Models\PlantLoss;
use App\Models\Season;

class PlantLossController extends Controller
{
    use LogsAudit;

    public function index(Season $season)
    {
        $this->authorize('viewAny', PlantLoss::class);

        return PlantLossResource::collection(
            $season->plantLosses()->orderByDesc('date')->get()
        );
    }

    public function store(StorePlantLossRequest $request)
    {
        $this->authorize('create', PlantLoss::class);

        $season = Season::findOrFail($request->validated('season_id'));

        $plantLoss = PlantLoss::create($request->validated() + [
            'company_id' => $request->user()->company_id,
            'hst_snapshot' => $season->planting_date->diffInDays($request->validated('date'), false),
            'created_by' => $request->user()->id,
        ]);

        $this->logAudit('create', $plantLoss, null, $plantLoss->toArray());

        return new PlantLossResource($plantLoss);
    }

    public function destroy(PlantLoss $plant_loss)
    {
        $this->authorize('delete', $plant_loss);

        $plant_loss->delete();
        $this->logAudit('delete', $plant_loss);

        return response()->json(['message' => 'Catatan tanaman mati berhasil dihapus.']);
    }
}
