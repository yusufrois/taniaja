<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\LogsAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGreenhouseRequest;
use App\Http\Requests\UpdateGreenhouseRequest;
use App\Http\Resources\GreenhouseResource;
use App\Models\Greenhouse;

class GreenhouseController extends Controller
{
    use LogsAudit;

    // NOTE: We intentionally do NOT use authorizeResource() here.
    // In Laravel 11/12's slimmed-down base Controller, authorizeResource()
    // internally calls the old-style instance method middleware(), which
    // no longer exists — it throws "Call to undefined method ...::middleware()".
    // Calling $this->authorize() explicitly in each action is the
    // version-proof equivalent and just as readable.

    public function index()
    {
        $this->authorize('viewAny', Greenhouse::class);

        return GreenhouseResource::collection(Greenhouse::orderBy('code')->paginate(20));
    }

    public function store(StoreGreenhouseRequest $request)
    {
        // Also enforced by StoreGreenhouseRequest::authorize(), kept here too
        // for defense-in-depth and consistency with the other actions.
        $this->authorize('create', Greenhouse::class);

        $greenhouse = Greenhouse::create($request->validated());
        $this->logAudit('create', $greenhouse, null, $greenhouse->toArray());

        return new GreenhouseResource($greenhouse);
    }

    public function show(Greenhouse $greenhouse)
    {
        $this->authorize('view', $greenhouse);

        return new GreenhouseResource($greenhouse);
    }

    public function update(UpdateGreenhouseRequest $request, Greenhouse $greenhouse)
    {
        $this->authorize('update', $greenhouse);

        $old = $greenhouse->toArray();
        $greenhouse->update($request->validated());
        $this->logAudit('update', $greenhouse, $old, $greenhouse->toArray());

        return new GreenhouseResource($greenhouse);
    }

    public function destroy(Greenhouse $greenhouse)
    {
        $this->authorize('delete', $greenhouse);

        $greenhouse->delete(); // soft delete
        $this->logAudit('delete', $greenhouse);

        return response()->json(['message' => 'Greenhouse berhasil dihapus.']);
    }
}
