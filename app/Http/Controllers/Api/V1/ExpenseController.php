<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\LogsAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use App\Services\Stock\StockBatchService;

class ExpenseController extends Controller
{
    use LogsAudit;

    public function index()
    {
        $this->authorize('viewAny', Expense::class);

        $query = Expense::with(['category', 'supplier'])->orderByDesc('date');

        // RBAC roadmap Fase A / A2: three-tier visibility (own/team/all)
        // — same pattern and precedence as PurchaseController::index().
        if (auth()->user()->canViewOwnOnlyOf('expense')) {
            $query->where('created_by', auth()->id());
        } elseif (auth()->user()->canViewTeamOnlyOf('expense')) {
            $query->whereIn('created_by', auth()->user()->teamUserIds());
        }

        return ExpenseResource::collection($query->paginate(20));
    }

    public function store(StoreExpenseRequest $request, StockBatchService $stockBatchService)
    {
        $this->authorize('create', Expense::class);

        $expense = Expense::create(
            $request->validated() + ['created_by' => $request->user()->id]
        );

        // If this expense is a trading landed cost (transport, sorting,
        // ...) linked to a Purchase, its StockBatch unit_cost needs to
        // reflect it — see StockBatchService::recalculateForPurchase()
        // for why this only applies before the batch has been sold from.
        if ($expense->purchase_id) {
            $stockBatchService->recalculateForPurchase($expense->purchase);
        }

        $this->logAudit('create', $expense, null, $expense->toArray());

        return new ExpenseResource($expense->load(['category', 'supplier']));
    }

    public function show(Expense $expense)
    {
        $this->authorize('view', $expense);

        return new ExpenseResource($expense->load(['category', 'supplier']));
    }

    public function destroy(Expense $expense)
    {
        $this->authorize('delete', $expense);

        $expense->delete();
        $this->logAudit('delete', $expense);

        return response()->json(['message' => 'Pengeluaran berhasil dihapus.']);
    }

    /**
     * POST /expenses/{expense}/approve — "Expense (approve)" row of the
     * Role & Permission Matrix, gated by ExpensePolicy::approve()
     * rather than the generic update() the base policy provides.
     */
    public function approve(Expense $expense)
    {
        $this->authorize('approve', $expense);

        abort_if($expense->isApproved(), 422, 'Pengeluaran ini sudah disetujui sebelumnya.');

        $expense->update([
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        $this->logAudit('expense.approve', $expense);

        return new ExpenseResource($expense);
    }
}
