<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\GuardsAgainstReferencedDeletion;

use App\Http\Controllers\Concerns\LogsAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVarietyRequest;
use App\Http\Requests\UpdateVarietyRequest;
use App\Http\Resources\VarietyResource;
use App\Models\Variety;

class VarietyController extends Controller
{
    use LogsAudit, GuardsAgainstReferencedDeletion;

    public function index()
    {
        $this->authorize('viewAny', Variety::class);

        return VarietyResource::collection(
            Variety::with('crop')->orderBy('name')->paginate(20)
        );
    }

    public function store(StoreVarietyRequest $request)
    {
        $this->authorize('create', Variety::class);

        $variety = Variety::create($request->validated());
        $this->logAudit('create', $variety, null, $variety->toArray());

        return new VarietyResource($variety->load('crop'));
    }

    public function show(Variety $variety)
    {
        $this->authorize('view', $variety);

        return new VarietyResource($variety->load('crop'));
    }

    public function update(UpdateVarietyRequest $request, Variety $variety)
    {
        $this->authorize('update', $variety);

        $old = $variety->toArray();
        $variety->update($request->validated());
        $this->logAudit('update', $variety, $old, $variety->toArray());

        return new VarietyResource($variety->load('crop'));
    }

    public function destroy(Variety $variety)
    {
        $this->authorize('delete', $variety);

        $this->assertNotReferenced([
            [\App\Models\Season::class, 'variety_id', $variety->id, 'data Musim Tanam'],
        ]);

        $variety->delete();
        $this->logAudit('delete', $variety);

        return response()->json(['message' => 'Variety berhasil dihapus.']);
    }
}
