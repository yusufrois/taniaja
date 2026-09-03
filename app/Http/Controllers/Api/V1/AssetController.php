<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\LogsAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use App\Http\Resources\AssetResource;
use App\Models\Asset;

class AssetController extends Controller
{
    use LogsAudit;

    public function index()
    {
        $this->authorize('viewAny', Asset::class);

        return AssetResource::collection(
            Asset::with('greenhouse')->orderByDesc('purchase_date')->paginate(20)
        );
    }

    public function store(StoreAssetRequest $request)
    {
        $this->authorize('create', Asset::class);

        $asset = Asset::create($request->validated());
        $this->logAudit('create', $asset, null, $asset->toArray());

        return new AssetResource($asset->load('greenhouse'));
    }

    public function show(Asset $asset)
    {
        $this->authorize('view', $asset);

        return new AssetResource($asset->load('greenhouse'));
    }

    public function update(UpdateAssetRequest $request, Asset $asset)
    {
        $this->authorize('update', $asset);

        $old = $asset->toArray();
        $asset->update($request->validated());
        $this->logAudit('update', $asset, $old, $asset->toArray());

        return new AssetResource($asset->load('greenhouse'));
    }

    public function destroy(Asset $asset)
    {
        $this->authorize('delete', $asset);

        $asset->delete();
        $this->logAudit('delete', $asset);

        return response()->json(['message' => 'Asset berhasil dihapus.']);
    }
}
