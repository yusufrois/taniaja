<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\GuardsAgainstReferencedDeletion;

use App\Http\Controllers\Concerns\LogsAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;

class SupplierController extends Controller
{
    use LogsAudit, GuardsAgainstReferencedDeletion;

    public function index()
    {
        $this->authorize('viewAny', Supplier::class);

        return SupplierResource::collection(Supplier::orderBy('name')->paginate(20));
    }

    public function store(StoreSupplierRequest $request)
    {
        $this->authorize('create', Supplier::class);

        $supplier = Supplier::create($request->validated());
        $this->logAudit('create', $supplier, null, $supplier->toArray());

        return new SupplierResource($supplier);
    }

    public function show(Supplier $supplier)
    {
        $this->authorize('view', $supplier);

        return new SupplierResource($supplier);
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier)
    {
        $this->authorize('update', $supplier);

        $old = $supplier->toArray();
        $supplier->update($request->validated());
        $this->logAudit('update', $supplier, $old, $supplier->toArray());

        return new SupplierResource($supplier);
    }

    public function destroy(Supplier $supplier)
    {
        $this->authorize('delete', $supplier);

        $this->assertNotReferenced([
            [\App\Models\Purchase::class, 'supplier_id', $supplier->id, 'data Pembelian'],
            [\App\Models\InputPurchase::class, 'supplier_id', $supplier->id, 'data Pembelian Pupuk'],
        ]);

        $supplier->delete();
        $this->logAudit('delete', $supplier);

        return response()->json(['message' => 'Supplier berhasil dihapus.']);
    }
}
