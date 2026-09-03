<?php

namespace App\Livewire\Capital;

use App\Models\CapitalTransaction;
use App\Models\ChartOfAccount;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * No edit here — CapitalTransactionController only exposes store/
 * show/destroy (no update()), matching real accounting practice:
 * once a capital movement is recorded, you correct it by voiding
 * (soft-delete) and re-entering, not silently editing history.
 */
#[Layout('layouts.app')]
class Manage extends Component
{
    use WithPagination;

    public bool $showModal = false;

    public string $type = 'owner_investment';
    public string $date = '';
    public ?float $amount = null;
    public ?int $chart_of_account_id = null;
    public string $source = '';
    public string $payment_method = '';
    public string $notes = '';

    public ?int $confirmingDeleteId = null;

    protected function rules(): array
    {
        return [
            'type' => ['required', \Illuminate\Validation\Rule::in(['owner_investment', 'investor_investment', 'withdrawal'])],
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'chart_of_account_id' => [
                'nullable',
                \Illuminate\Validation\Rule::exists('chart_of_accounts', 'id')
                    ->where('company_id', auth()->user()->company_id)->where('type', 'asset'),
            ],
            'source' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function mount(): void
    {
        $this->authorize('viewAny', CapitalTransaction::class);
    }

    public function openCreate(): void
    {
        $this->authorize('create', CapitalTransaction::class);
        $this->resetForm();
        $this->date = now()->toDateString();
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize('create', CapitalTransaction::class);
        $data = $this->validate();

        CapitalTransaction::create($data + [
            'company_id' => auth()->user()->company_id,
            'created_by' => auth()->id(),
        ]);

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $transaction = CapitalTransaction::findOrFail($id);
        $this->authorize('delete', $transaction);
        $this->confirmingDeleteId = $id;
    }

    public function delete(): void
    {
        $transaction = CapitalTransaction::findOrFail($this->confirmingDeleteId);
        $this->authorize('delete', $transaction);
        $transaction->delete();
        $this->confirmingDeleteId = null;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->type = 'owner_investment';
        $this->date = '';
        $this->amount = null;
        $this->chart_of_account_id = null;
        $this->source = '';
        $this->payment_method = '';
        $this->notes = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.capital.manage', [
            'transactions' => CapitalTransaction::with('creator')->orderByDesc('date')->paginate(10),
            'accounts' => ChartOfAccount::where('is_active', true)->where('type', 'asset')->orderBy('code')->get(),
            'canCreate' => $user->can('create', CapitalTransaction::class),
            'canViewCost' => $user->hasPermission('cost.view'),
        ]);
    }
}
