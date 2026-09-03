<?php

namespace App\Livewire\Expense;

use App\Models\ChartOfAccount;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Greenhouse;
use App\Models\Season;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Core fields only for this first pass — greenhouse_id/season_id
 * (cost allocation) and chart_of_account_id (Fase L5, which Kas/Bank
 * paid it) are included since they're commonly useful; purchase_id
 * linking (landed-cost expenses), supplier_id, transaction_no, and
 * attachment upload are deliberately deferred (see README) to keep
 * this first Keuangan page manageable — those go through the API
 * directly for now if needed.
 */
#[Layout('layouts.app')]
class Manage extends Component
{
    use WithPagination;

    public bool $showModal = false;
    public ?int $editingId = null;

    public ?int $greenhouse_id = null;
    public ?int $season_id = null;
    public ?int $expense_category_id = null;
    public string $date = '';
    public ?float $amount = null;
    public ?int $chart_of_account_id = null;
    public string $payment_method = '';
    public string $description = '';

    public ?int $confirmingDeleteId = null;

    protected function rules(): array
    {
        $companyId = auth()->user()->company_id;

        return [
            'greenhouse_id' => ['nullable', \Illuminate\Validation\Rule::exists('greenhouses', 'id')->where('company_id', $companyId)],
            'season_id' => ['nullable', \Illuminate\Validation\Rule::exists('seasons', 'id')->where('company_id', $companyId)],
            'expense_category_id' => ['required', \Illuminate\Validation\Rule::exists('expense_categories', 'id')->where('company_id', $companyId)],
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'chart_of_account_id' => ['nullable', \Illuminate\Validation\Rule::exists('chart_of_accounts', 'id')->where('company_id', $companyId)->where('type', 'asset')],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function mount(): void
    {
        $this->authorize('viewAny', Expense::class);
    }

    /**
     * "Musim Tanam yang muncul cuma yang ada di Greenhouse itu" — reset
     * the (now possibly-invalid) season choice whenever greenhouse
     * changes, same cascading pattern as Season's crop→variety.
     */
    public function updatedGreenhouseId(): void
    {
        $this->season_id = null;
    }

    public function openCreate(): void
    {
        $this->authorize('create', Expense::class);
        $this->resetForm();
        $this->date = now()->toDateString();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $expense = Expense::findOrFail($id);
        $this->authorize('update', $expense);

        $this->editingId = $expense->id;
        $this->greenhouse_id = $expense->greenhouse_id;
        $this->season_id = $expense->season_id;
        $this->expense_category_id = $expense->expense_category_id;
        $this->date = $expense->date?->toDateString() ?? '';
        $this->amount = $expense->amount;
        $this->chart_of_account_id = $expense->chart_of_account_id;
        $this->payment_method = $expense->payment_method ?? '';
        $this->description = $expense->description ?? '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            $expense = Expense::findOrFail($this->editingId);
            $this->authorize('update', $expense);
            $expense->update($data);
        } else {
            $this->authorize('create', Expense::class);
            Expense::create($data + ['company_id' => auth()->user()->company_id, 'created_by' => auth()->id()]);
        }

        $this->showModal = false;
        $this->resetForm();
    }

    /** POST /expenses/{id}/approve equivalent — same ExpensePolicy::approve() gate as the API. */
    public function approve(int $id): void
    {
        $expense = Expense::findOrFail($id);
        $this->authorize('approve', $expense);

        $expense->update(['approved_at' => now(), 'approved_by' => auth()->id()]);
    }

    public function confirmDelete(int $id): void
    {
        $expense = Expense::findOrFail($id);
        $this->authorize('delete', $expense);
        $this->confirmingDeleteId = $id;
    }

    public function delete(): void
    {
        $expense = Expense::findOrFail($this->confirmingDeleteId);
        $this->authorize('delete', $expense);
        $expense->delete();
        $this->confirmingDeleteId = null;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->greenhouse_id = null;
        $this->season_id = null;
        $this->expense_category_id = null;
        $this->date = '';
        $this->amount = null;
        $this->chart_of_account_id = null;
        $this->payment_method = '';
        $this->description = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.expense.manage', [
            'expenses' => Expense::with(['category', 'greenhouse', 'season', 'creator', 'approver'])->orderByDesc('date')->paginate(10),
            'categories' => ExpenseCategory::orderBy('name')->get(),
            'greenhouses' => Greenhouse::orderBy('code')->get(),
            'seasons' => Season::whereIn('status', ['planning', 'active', 'harvesting'])
                ->when($this->greenhouse_id, fn ($q) => $q->where('greenhouse_id', $this->greenhouse_id))
                ->orderByDesc('planting_date')->get(),
            'accounts' => ChartOfAccount::where('is_active', true)->where('type', 'asset')->orderBy('code')->get(),
            'canCreate' => $user->can('create', Expense::class),
            'canApprove' => $user->hasPermission('expense.approve'),
            'canViewCost' => $user->hasPermission('cost.view'),
        ]);
    }
}
