<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\LogsAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAssetCategoryRequest;
use App\Http\Resources\AssetCategoryResource;
use App\Models\Asset;
use App\Models\AssetCategory;

class AssetCategoryController extends Controller
{
    use LogsAudit;

    public function index()
    {
        $this->authorize('viewAny', AssetCategory::class);

        return AssetCategoryResource::collection(AssetCategory::orderBy('name')->get());
    }

    public function store(StoreAssetCategoryRequest $request)
    {
        $this->authorize('create', AssetCategory::class);

        $category = AssetCategory::create($request->validated() + ['company_id' => $request->user()->company_id]);
        $this->logAudit('create', $category, null, $category->toArray());

        return new AssetCategoryResource($category);
    }

    public function destroy(AssetCategory $asset_category)
    {
        $this->authorize('delete', $asset_category);

        // Different from the other master-data guards: Asset.category
        // is matched by NAME (a plain string, not a foreign key) —
        // see AssetCategory model's docblock for why it was designed
        // that way — so the check is a name match, not an id match.
        if (Asset::where('category', $asset_category->name)->exists()) {
            abort(422, 'Tidak bisa dihapus — masih dipakai di data Aset Tetap. Ganti dulu kategori aset yang memakainya sebelum menghapus.');
        }

        $asset_category->delete();
        $this->logAudit('delete', $asset_category);

        return response()->json(['message' => 'Kategori aset berhasil dihapus.']);
    }
}
