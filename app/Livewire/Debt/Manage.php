<?php

namespace App\Livewire\Debt;

use App\Models\ChartOfAccount;
use App\Models\Debt;
use App\Models\Greenhouse;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Two separate modals, matching the API's separation of concerns
 * (Section 15): creating a Debt (the loan itself, no update() exists
 * either — same "void and re-enter" philosophy as Capital) vs
 * recording a Payment against it (a fully separate sub-resource, so
 * payment history is preserved rather than the debt holding one
 * running total).
 */
#[Layout('layouts.app')]
class Manage extends Component
{
    use WithPagination;

    public bool $showCreateModal = false;
    public bool $showPaymentModal = false;
    public ?int $payingDebtId = null;

    public ?int $greenhouse_id = null;
    public string $creditor_name = '';
    public string $debt_date = '';
    public string $due_date = '';
    public ?float $amount = null;
    public ?int $installment_months = null;
    public ?int $chart_of_account_id = null;
    public string $notes = '';

    public bool $showDetailModal = false;
    public ?int $viewingDebtId = null;

    public string $payment_date = '';
    public ?float $payment_amount = null;
    public ?int $payment_chart_of_account_id = null;
    public string $payment_method = '';
    public string $payment_notes = '';

    public ?int $confirmingDeleteId = null;

    protected function rules(): array
    {
        $companyId = auth()->user()->company_id;

        return [
            'greenhouse_id' => ['nullable', \Illuminate\Validation\Rule::exists('greenhouses', 'id')->where('company_id', $companyId)],
            'creditor_name' => ['required', 'string', 'max:255'],
            'debt_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:debt_date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'installment_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'chart_of_account_id' => ['nullable', \Illuminate\Validation\Rule::exists('chart_of_accounts', 'id')->where('company_id', $companyId)->where('type', 'asset')],
            'notes' => ['nullable', 'string'],
        ];
    }

    private function paymentRules(Debt $debt): array
    {
        $companyId = auth()->user()->company_id;

        return [
            'payment_date' => ['required', 'date'],
            'payment_amount' => ['required', 'numeric', 'min:0.01', 'max:'.$debt->remaining],
            'payment_chart_of_account_id' => ['nullable', \Illuminate\Validation\Rule::exists('chart_of_accounts', 'id')->where('company_id', $companyId)->where('type', 'asset')],
            'payment_notes' => ['nullable', 'string'],
        ];
    }

    public function mount(): void
    {
        $this->authorize('viewAny', Debt::class);
    }

    public function openCreate(): void
    {
        $this->authorize('create', Debt::class);
        $this->resetForm();
        $this->debt_date = now()->toDateString();
        $this->showCreateModal = true;
    }

    public function save(): void
    {
        $this->authorize('create', Debt::class);
        $data = $this->validate();

        Debt::create($data + ['company_id' => auth()->user()->company_id]);

        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function openPayment(int $debtId): void
    {
        $debt = Debt::findOrFail($debtId);
        $this->authorize('update', $debt);

        $this->payingDebtId = $debtId;
        $this->payment_date = now()->toDateString();
        $this->payment_amount = null;
        $this->payment_chart_of_account_id = null;
        $this->payment_method = '';
        $this->payment_notes = '';
        $this->resetErrorBag();
        $this->showPaymentModal = true;
    }

    /**
     * Same overpayment guard as StoreDebtPaymentRequest — a payment
     * can never exceed what's actually still owed (Aturan #48).
     */
    public function savePayment(): void
    {
        $debt = Debt::findOrFail($this->payingDebtId);
        $this->authorize('update', $debt);

        $this->validate($this->paymentRules($debt), [
            'payment_amount.max' => 'Jumlah pembayaran melebihi sisa hutang (Rp'.number_format($debt->remaining, 0, ',', '.').').',
        ]);

        $debt->payments()->create([
            'company_id' => $debt->company_id,
            'payment_date' => $this->payment_date,
            'amount' => $this->payment_amount,
            'chart_of_account_id' => $this->payment_chart_of_account_id,
            'payment_method' => $this->payment_method ?: null,
            'notes' => $this->payment_notes ?: null,
        ]);

        $this->showPaymentModal = false;
    }

    public function closePaymentModal(): void
    {
        $this->showPaymentModal = false;
        $this->resetErrorBag();
    }

    public function confirmDelete(int $id): void
    {
        $debt = Debt::findOrFail($id);
        $this->authorize('delete', $debt);
        $this->confirmingDeleteId = $id;
    }

    public function delete(): void
    {
        $debt = Debt::findOrFail($this->confirmingDeleteId);
        $this->authorize('delete', $debt);
        $debt->delete();
        $this->confirmingDeleteId = null;
    }

    public function closeModal(): void
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }

    /** Roadmap tambahan — "riwayat semua pembayaran" + jadwal cicilan. */
    public function openDetail(int $id): void
    {
        $debt = Debt::findOrFail($id);
        $this->authorize('view', $debt);

        $this->viewingDebtId = $id;
        $this->showDetailModal = true;
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->viewingDebtId = null;
    }

    private function resetForm(): void
    {
        $this->greenhouse_id = null;
        $this->creditor_name = '';
        $this->debt_date = '';
        $this->due_date = '';
        $this->amount = null;
        $this->installment_months = null;
        $this->chart_of_account_id = null;
        $this->notes = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.debt.manage', [
            'debts' => Debt::orderByDesc('debt_date')->paginate(10),
            'greenhouses' => Greenhouse::orderBy('code')->get(),
            'accounts' => ChartOfAccount::where('is_active', true)->where('type', 'asset')->orderBy('code')->get(),
            'viewingDebt' => $this->viewingDebtId ? Debt::with('payments')->find($this->viewingDebtId) : null,
            'canCreate' => $user->can('create', Debt::class),
            'canViewCost' => $user->hasPermission('cost.view'),
        ]);
    }
}
