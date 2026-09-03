<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\LogsAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInputUsageRequest;
use App\Http\Resources\InputUsageResource;
use App\Models\Expense;
use App\Models\InputItem;
use App\Models\InputUsage;
use Illuminate\Support\Facades\DB;

class InputUsageController extends Controller
{
    use LogsAudit;

    public function index()
    {
        $this->authorize('viewAny', InputUsage::class);

        return InputUsageResource::collection(
            InputUsage::with('inputItem')->orderByDesc('used_date')->paginate(20)
        );
    }

    /**
     * This is where input-stock cost is actually recognized — NOT at
     * purchase time (see InputPurchaseController's docblock for why).
     * cost = quantity × InputItem::averageUnitCost() AT THIS MOMENT,
     * frozen onto the InputUsage row (never retroactively recalculated
     * later, same "unit_cost frozen at first sale" principle as
     * StockBatch, Phase 6). If the InputItem has a
     * default_expense_category_id set, an Expense is auto-created for
     * that cost, tagged with THIS usage's greenhouse_id/season_id — so
     * cost correctly follows wherever the fertilizer was actually
     * applied, letting GH-A and GH-B show different pupuk cost even
     * when both draw from the same bulk-bought stock.
     */
    public function store(StoreInputUsageRequest $request)
    {
        $this->authorize('create', InputUsage::class);

        $validated = $request->validated();

        $usage = DB::transaction(function () use ($validated, $request) {
            $inputItem = InputItem::findOrFail($validated['input_item_id']);
            $cost = round($validated['quantity'] * $inputItem->averageUnitCost(), 2);

            $expenseId = null;

            if ($inputItem->default_expense_category_id) {
                $expense = Expense::create([
                    'company_id' => $request->user()->company_id,
                    'greenhouse_id' => $validated['greenhouse_id'] ?? null,
                    'season_id' => $validated['season_id'] ?? null,
                    'expense_category_id' => $inputItem->default_expense_category_id,
                    'date' => $validated['used_date'],
                    'amount' => $cost,
                    'description' => "Pemakaian {$inputItem->name} ({$validated['quantity']} {$inputItem->unit})",
                    'created_by' => $request->user()->id,
                    // Roadmap tambahan — Beban "Menunggu" now excluded from
                    // reports until approved. This one is auto-approved:
                    // it's a SYSTEM-COMPUTED cost from an actual recorded
                    // usage (quantity × frozen average cost), not a manual
                    // claim someone needs to verify — the approval workflow
                    // exists for human-submitted expense claims, not for
                    // facts the system already derived from its own data.
                    'approved_at' => now(),
                    'approved_by' => $request->user()->id,
                ]);
                $expenseId = $expense->id;
            }

            return InputUsage::create($validated + [
                'company_id' => $request->user()->company_id,
                'cost' => $cost,
                'expense_id' => $expenseId,
                'created_by' => $request->user()->id,
            ]);
        });

        $this->logAudit('create', $usage, null, $usage->toArray());

        return new InputUsageResource($usage->load('inputItem'));
    }

    public function show(InputUsage $input_usage)
    {
        $this->authorize('view', $input_usage);

        return new InputUsageResource($input_usage->load('inputItem'));
    }

    /**
     * Soft-deleting restores the stock automatically (currentStock() is
     * computed live, excluding soft-deleted usages). Deliberately does
     * NOT touch the linked Expense — same "void, don't erase financial
     * transactions" principle as InputPurchase's destroy() and Section
     * 30 generally. Correct/void the Expense separately if needed.
     */
    public function destroy(InputUsage $input_usage)
    {
        $this->authorize('delete', $input_usage);

        $input_usage->delete();
        $this->logAudit('delete', $input_usage);

        return response()->json([
            'message' => 'Catatan pemakaian berhasil dihapus, stok otomatis kembali. '.
                'Catatan Expense terkait TIDAK ikut terhapus.',
        ]);
    }
}
