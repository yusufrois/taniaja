<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\GuardsAgainstReferencedDeletion;

use App\Http\Controllers\Concerns\LogsAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExpenseCategoryRequest;
use App\Http\Requests\UpdateExpenseCategoryRequest;
use App\Http\Resources\ExpenseCategoryResource;
use App\Models\ExpenseCategory;

class ExpenseCategoryController extends Controller
{
    use LogsAudit, GuardsAgainstReferencedDeletion;

    public function index()
    {
        $this->authorize('viewAny', ExpenseCategory::class);

        return ExpenseCategoryResource::collection(ExpenseCategory::orderBy('name')->paginate(20));
    }

    public function store(StoreExpenseCategoryRequest $request)
    {
        $this->authorize('create', ExpenseCategory::class);

        $category = ExpenseCategory::create($request->validated());
        $this->logAudit('create', $category, null, $category->toArray());

        return new ExpenseCategoryResource($category);
    }

    public function show(ExpenseCategory $expense_category)
    {
        $this->authorize('view', $expense_category);

        return new ExpenseCategoryResource($expense_category);
    }

    public function update(UpdateExpenseCategoryRequest $request, ExpenseCategory $expense_category)
    {
        $this->authorize('update', $expense_category);

        $old = $expense_category->toArray();
        $expense_category->update($request->validated());
        $this->logAudit('update', $expense_category, $old, $expense_category->toArray());

        return new ExpenseCategoryResource($expense_category);
    }

    public function destroy(ExpenseCategory $expense_category)
    {
        $this->authorize('delete', $expense_category);

        $this->assertNotReferenced([
            [\App\Models\Expense::class, 'expense_category_id', $expense_category->id, 'data Beban'],
        ]);

        $expense_category->delete();
        $this->logAudit('delete', $expense_category);

        return response()->json(['message' => 'Expense category berhasil dihapus.']);
    }
}
