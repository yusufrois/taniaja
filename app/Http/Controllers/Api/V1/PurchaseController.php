<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\LogsAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePurchasePaymentRequest;
use App\Http\Requests\StorePurchaseRequest;
use App\Http\Resources\PurchaseResource;
use App\Http\Resources\PurchasePaymentResource;
use App\Models\Purchase;
use App\Services\Stock\StockBatchService;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    use LogsAudit;

    public function index()
    {
        $this->authorize('viewAny', Purchase::class);

        $query = Purchase::with('supplier')->orderByDesc('purchase_date');

        // RBAC roadmap Fase A / A2: three-tier visibility. canViewOwnOnlyOf()
        // and canViewTeamOnlyOf() are mutually exclusive by construction
        // (each explicitly requires the tier above it to be ABSENT — see
        // User model), so a plain if/elseif here is safe and correct.
        if (auth()->user()->canViewOwnOnlyOf('purchase')) {
            $query->where('created_by', auth()->id());
        } elseif (auth()->user()->canViewTeamOnlyOf('purchase')) {
            $query->whereIn('created_by', auth()->user()->teamUserIds());
        }

        return PurchaseResource::collection($query->paginate(20));
    }

    /**
     * Creates the Purchase record AND its StockBatch together — the
     * "beli dari petani" side of the tengkulak workflow. total_amount
     * is computed server-side (never trusted from the client, per
     * Aturan #35) from quantity * unit_price.
     */
    public function store(StorePurchaseRequest $request, StockBatchService $stockBatchService)
    {
        $this->authorize('create', Purchase::class);

        $validated = $request->validated();
        $totalAmount = round($validated['quantity'] * $validated['unit_price'], 2);

        $purchase = DB::transaction(function () use ($validated, $totalAmount, $request, $stockBatchService) {
            $purchase = Purchase::create($validated + [
                'company_id' => $request->user()->company_id,
                'total_amount' => $totalAmount,
                'created_by' => $request->user()->id,
            ]);

            $stockBatchService->createFromPurchase($purchase);

            return $purchase;
        });

        $this->logAudit('create', $purchase, null, $purchase->toArray());

        return new PurchaseResource($purchase->load('supplier'));
    }

    public function show(Purchase $purchase)
    {
        $this->authorize('view', $purchase);

        return new PurchaseResource($purchase->load(['supplier', 'stockBatch', 'payments']));
    }

    public function destroy(Purchase $purchase)
    {
        $this->authorize('delete', $purchase);

        $purchase->delete();
        $this->logAudit('delete', $purchase);

        return response()->json(['message' => 'Pembelian berhasil dihapus.']);
    }

    /**
     * IMPORTANT: adding a payment does NOT retroactively recompute the
     * linked StockBatch.unit_cost — purchase payments settle what's
     * owed to the supplier, they don't change the goods' landed cost
     * (that's driven by total_amount + linked Expense, fixed at
     * purchase time). Keeping these separate avoids a batch's cost
     * silently shifting after it may have already been partially sold.
     */
    public function storePayment(StorePurchasePaymentRequest $request, Purchase $purchase)
    {
        $payment = $purchase->payments()->create(
            $request->validated() + ['company_id' => $purchase->company_id]
        );

        $this->logAudit('purchase.payment', $purchase, null, $payment->toArray());

        return new PurchasePaymentResource($payment);
    }

    /**
     * Roadmap Fase E — "Nota Pembayaran ke Petani" as PDF, shareable
     * via WhatsApp right from the field. No new data — this is purely
     * a print/PDF view of the Purchase record that already existed
     * since Phase 6.
     *
     * Requires barryvdh/laravel-dompdf (composer require
     * barryvdh/laravel-dompdf) — see README-FASE-DE.
     */
    public function pdf(Purchase $purchase)
    {
        $this->authorize('view', $purchase);

        $purchase->load(['supplier', 'crop', 'variety', 'grade', 'creator', 'company']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.purchase-receipt', ['purchase' => $purchase]);

        return $pdf->stream("nota-{$purchase->id}.pdf");
    }
}
