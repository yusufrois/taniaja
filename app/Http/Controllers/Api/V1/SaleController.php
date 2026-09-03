<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\LogsAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSalePaymentRequest;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Resources\SalePaymentResource;
use App\Http\Resources\SaleResource;
use App\Models\Sale;
use App\Services\Sales\SalesService;

class SaleController extends Controller
{
    use LogsAudit;

    public function index()
    {
        $this->authorize('viewAny', Sale::class);

        $query = Sale::with('customer')->orderByDesc('date');

        // RBAC roadmap Fase A / A2: three-tier visibility (own/team/all)
        // — same pattern and precedence as PurchaseController::index().
        if (auth()->user()->canViewOwnOnlyOf('sale')) {
            $query->where('created_by', auth()->id());
        } elseif (auth()->user()->canViewTeamOnlyOf('sale')) {
            $query->whereIn('created_by', auth()->user()->teamUserIds());
        }

        return SaleResource::collection($query->paginate(20));
    }

    public function store(StoreSaleRequest $request, SalesService $salesService)
    {
        $this->authorize('create', Sale::class);

        $header = $request->safe()->only([
            'customer_id', 'greenhouse_id', 'season_id', 'date', 'due_date', 'discount', 'tax', 'notes',
        ]);
        $header['company_code'] = $request->user()->company->code;

        $sale = $salesService->create($header, $request->validated('items'), $request->user());

        $this->logAudit('create', $sale, null, $sale->toArray());

        return new SaleResource($sale->load(['customer', 'items']));
    }

    public function show(Sale $sale)
    {
        $this->authorize('view', $sale);

        return new SaleResource($sale->load(['customer', 'items', 'payments']));
    }

    public function destroy(Sale $sale)
    {
        $this->authorize('delete', $sale);

        // NOTE: deleting a Sale does NOT restore quantity_available on
        // its items' StockBatches — matches Section 30/48 (correctness
        // and data integrity first): a sale being voided in bookkeeping
        // doesn't necessarily mean the physical goods came back into
        // stock. Restocking, if needed, is a deliberate separate action
        // (out of scope for this MVP) rather than an automatic side effect.
        $sale->delete();
        $this->logAudit('delete', $sale);

        return response()->json(['message' => 'Invoice berhasil dihapus (soft delete).']);
    }

    public function storePayment(StoreSalePaymentRequest $request, Sale $sale)
    {
        $payment = $sale->payments()->create(
            $request->validated() + ['company_id' => $sale->company_id]
        );

        $this->logAudit('sale.payment', $sale, null, $payment->toArray());

        return new SalePaymentResource($payment);
    }
}
