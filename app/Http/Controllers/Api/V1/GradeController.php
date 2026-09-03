<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\GuardsAgainstReferencedDeletion;

use App\Http\Controllers\Concerns\LogsAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGradeRequest;
use App\Http\Requests\UpdateGradeRequest;
use App\Http\Resources\GradeResource;
use App\Models\Grade;

class GradeController extends Controller
{
    use LogsAudit, GuardsAgainstReferencedDeletion;

    public function index()
    {
        $this->authorize('viewAny', Grade::class);

        return GradeResource::collection(Grade::orderBy('sort_order')->paginate(20));
    }

    public function store(StoreGradeRequest $request)
    {
        $this->authorize('create', Grade::class);

        $grade = Grade::create($request->validated());
        $this->logAudit('create', $grade, null, $grade->toArray());

        return new GradeResource($grade);
    }

    public function show(Grade $grade)
    {
        $this->authorize('view', $grade);

        return new GradeResource($grade);
    }

    public function update(UpdateGradeRequest $request, Grade $grade)
    {
        $this->authorize('update', $grade);

        $old = $grade->toArray();
        $grade->update($request->validated());
        $this->logAudit('update', $grade, $old, $grade->toArray());

        return new GradeResource($grade);
    }

    public function destroy(Grade $grade)
    {
        $this->authorize('delete', $grade);

        $this->assertNotReferenced([
            [\App\Models\HarvestItem::class, 'grade_id', $grade->id, 'data Hasil Panen'],
        ]);

        $grade->delete();
        $this->logAudit('delete', $grade);

        return response()->json(['message' => 'Grade berhasil dihapus.']);
    }
}
