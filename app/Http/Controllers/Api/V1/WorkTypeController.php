<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\LogsAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWorkTypeRequest;
use App\Http\Requests\UpdateWorkTypeRequest;
use App\Http\Resources\WorkTypeResource;
use App\Models\WorkType;

class WorkTypeController extends Controller
{
    use LogsAudit;

    public function index()
    {
        $this->authorize('viewAny', WorkType::class);

        return WorkTypeResource::collection(WorkType::orderBy('name')->paginate(20));
    }

    public function store(StoreWorkTypeRequest $request)
    {
        $this->authorize('create', WorkType::class);

        $workType = WorkType::create($request->validated() + ['company_id' => $request->user()->company_id]);
        $this->logAudit('create', $workType, null, $workType->toArray());

        return new WorkTypeResource($workType);
    }

    public function show(WorkType $work_type)
    {
        $this->authorize('view', $work_type);

        return new WorkTypeResource($work_type);
    }

    public function update(UpdateWorkTypeRequest $request, WorkType $work_type)
    {
        $this->authorize('update', $work_type);

        $old = $work_type->toArray();
        $work_type->update($request->validated());
        $this->logAudit('update', $work_type, $old, $work_type->toArray());

        return new WorkTypeResource($work_type);
    }

    public function destroy(WorkType $work_type)
    {
        $this->authorize('delete', $work_type);

        $work_type->delete();
        $this->logAudit('delete', $work_type);

        return response()->json(['message' => 'Jenis pekerjaan berhasil dihapus.']);
    }
}
