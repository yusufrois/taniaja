<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\LogsAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInputPurchaseRequest;
use App\Http\Resources\InputPurchaseResource;
use App\Models\InputPurchase;

/**
 * Revision note: this used to ALSO create an Expense on every purchase
 * (cash-basis, cost recognized at acquisition). That over-attributed
 * cost to wherever the bulk purchase happened to be tagged, which is
 * often not where the fertilizer actually ends up being used. Cost is
 * now recognized at USAGE time instead — see InputUsageController.
 * This controller's only job is stock intake: how much was bought, at
 * what price, contributing to InputItem::averageUnitCost().
 */
class InputPurchaseController extends Controller
{
    use LogsAudit;

    public function index()
    {
        $this->authorize('viewAny', InputPurchase::class);

        return InputPurchaseResource::collection(
            InputPurchase::with(['inputItem', 'supplier'])->orderByDesc('purchase_date')->paginate(20)
        );
    }

    public function store(StoreInputPurchaseRequest $request)
    {
        $this->authorize('create', InputPurchase::class);

        $validated = $request->validated();
        $totalAmount = round($validated['quantity'] * $validated['unit_price'], 2);

        $purchase = InputPurchase::create($validated + [
            'company_id' => $request->user()->company_id,
            'total_amount' => $totalAmount,
            'created_by' => $request->user()->id,
        ]);

        $this->logAudit('create', $purchase, null, $purchase->toArray());

        return new InputPurchaseResource($purchase->load(['inputItem', 'supplier']));
    }

    public function show(InputPurchase $input_purchase)
    {
        $this->authorize('view', $input_purchase);

        return new InputPurchaseResource($input_purchase->load(['inputItem', 'supplier']));
    }

    public function destroy(InputPurchase $input_purchase)
    {
        $this->authorize('delete', $input_purchase);

        $input_purchase->delete();
        $this->logAudit('delete', $input_purchase);

        return response()->json(['message' => 'Pembelian input berhasil dihapus.']);
    }
}
