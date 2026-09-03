<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\LogsAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDebtPaymentRequest;
use App\Http\Requests\StoreDebtRequest;
use App\Http\Resources\DebtPaymentResource;
use App\Http\Resources\DebtResource;
use App\Models\Debt;

class DebtController extends Controller
{
    use LogsAudit;

    public function index()
    {
        $this->authorize('viewAny', Debt::class);

        return DebtResource::collection(
            Debt::orderByDesc('due_date')->paginate(20)
        );
    }

    public function store(StoreDebtRequest $request)
    {
        $this->authorize('create', Debt::class);

        $debt = Debt::create($request->validated());
        $this->logAudit('create', $debt, null, $debt->toArray());

        return new DebtResource($debt);
    }

    public function show(Debt $debt)
    {
        $this->authorize('view', $debt);

        return new DebtResource($debt->load('payments'));
    }

    public function destroy(Debt $debt)
    {
        $this->authorize('delete', $debt);

        $debt->delete();
        $this->logAudit('delete', $debt);

        return response()->json(['message' => 'Hutang berhasil dihapus.']);
    }

    /**
     * POST /debts/{debt}/payments — recorded as a SEPARATE resource
     * from the debt itself (Section 15: "Pembayaran hutang harus
     * dicatat terpisah"), so the full payment history is preserved
     * rather than the debt just holding a single running total.
     */
    public function storePayment(StoreDebtPaymentRequest $request, Debt $debt)
    {
        $payment = $debt->payments()->create(
            $request->validated() + ['company_id' => $debt->company_id]
        );

        $this->logAudit('debt.payment', $debt, null, $payment->toArray());

        return new DebtPaymentResource($payment);
    }
}
