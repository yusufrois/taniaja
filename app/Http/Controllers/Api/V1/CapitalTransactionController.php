<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\LogsAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCapitalTransactionRequest;
use App\Http\Resources\CapitalTransactionResource;
use App\Models\CapitalTransaction;

class CapitalTransactionController extends Controller
{
    use LogsAudit;

    public function index()
    {
        $this->authorize('viewAny', CapitalTransaction::class);

        return CapitalTransactionResource::collection(
            CapitalTransaction::orderByDesc('date')->paginate(20)
        );
    }

    public function store(StoreCapitalTransactionRequest $request)
    {
        $this->authorize('create', CapitalTransaction::class);

        $transaction = CapitalTransaction::create(
            $request->validated() + ['created_by' => $request->user()->id]
        );
        $this->logAudit('create', $transaction, null, $transaction->toArray());

        return new CapitalTransactionResource($transaction);
    }

    public function show(CapitalTransaction $capital_transaction)
    {
        $this->authorize('view', $capital_transaction);

        return new CapitalTransactionResource($capital_transaction);
    }

    public function destroy(CapitalTransaction $capital_transaction)
    {
        $this->authorize('delete', $capital_transaction);

        // Soft delete only, per Section 30 — financial transactions are
        // never hard-deleted, even by an authorized user.
        $capital_transaction->delete();
        $this->logAudit('delete', $capital_transaction);

        return response()->json(['message' => 'Transaksi modal berhasil dihapus (soft delete).']);
    }
}
