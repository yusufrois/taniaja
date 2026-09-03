<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\GuardsAgainstReferencedDeletion;

use App\Http\Controllers\Concerns\LogsAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCropRequest;
use App\Http\Requests\UpdateCropRequest;
use App\Http\Resources\CropResource;
use App\Models\Crop;

class CropController extends Controller
{
    use LogsAudit, GuardsAgainstReferencedDeletion;

    public function index()
    {
        $this->authorize('viewAny', Crop::class);

        return CropResource::collection(
            Crop::with('varieties')->orderBy('name')->paginate(20)
        );
    }

    public function store(StoreCropRequest $request)
    {
        $this->authorize('create', Crop::class);

        $crop = Crop::create($request->validated());
        $this->logAudit('create', $crop, null, $crop->toArray());

        return new CropResource($crop);
    }

    public function show(Crop $crop)
    {
        $this->authorize('view', $crop);

        return new CropResource($crop->load('varieties'));
    }

    public function update(UpdateCropRequest $request, Crop $crop)
    {
        $this->authorize('update', $crop);

        $old = $crop->toArray();
        $crop->update($request->validated());
        $this->logAudit('update', $crop, $old, $crop->toArray());

        return new CropResource($crop);
    }

    public function destroy(Crop $crop)
    {
        $this->authorize('delete', $crop);

        $this->assertNotReferenced([
            [\App\Models\Variety::class, 'crop_id', $crop->id, 'data Varietas'],
            [\App\Models\Season::class, 'crop_id', $crop->id, 'data Musim Tanam'],
        ]);

        $crop->delete();
        $this->logAudit('delete', $crop);

        return response()->json(['message' => 'Crop berhasil dihapus.']);
    }
}
