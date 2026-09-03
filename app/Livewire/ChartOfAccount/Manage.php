<?php

namespace App\Livewire\ChartOfAccount;

use App\Models\ChartOfAccount;
use App\Services\Accounting\StandardChartOfAccountsSeeder;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * The Chart of Accounts (Fase L1) never had a UI page until now — it
 * only existed via the API. Without this, "Dibayar dari Akun" dropdowns
 * everywhere (Expense, Capital, Debt — Fase L5) stayed permanently
 * empty for any company that hadn't manually called the seed-defaults
 * API endpoint via Postman, which most people would never think to do.
 */
#[Layout('layouts.app')]
class Manage extends Component
{
    public bool $showModal = false;
    public string $code = '';
    public string $name = '';
    public string $type = 'asset';

    protected function rules(): array
    {
        return [
            'code' => [
                'required', 'string', 'max:20',
                \Illuminate\Validation\Rule::unique('chart_of_accounts', 'code')->where('company_id', auth()->user()->company_id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', \Illuminate\Validation\Rule::in(['asset', 'liability', 'equity', 'revenue', 'expense'])],
        ];
    }

    public function mount(): void
    {
        $this->authorize('viewAny', ChartOfAccount::class);
    }

    /**
     * Idempotent — safe to click even if some/all standard accounts
     * already exist (StandardChartOfAccountsSeeder uses firstOrCreate).
     */
    public function seedDefaults(StandardChartOfAccountsSeeder $seeder): void
    {
        $this->authorize('create', ChartOfAccount::class);
        $seeder->seedFor(auth()->user()->company);
    }

    /** Roadmap tambahan — transaksi lama yang dicatat sebelum Bagan Akun ada. */
    public bool $showBackfillResult = false;
    public array $backfillResult = [];

    public function backfillOldTransactions(\App\Services\Accounting\AccountingBackfillService $backfill): void
    {
        $this->authorize('create', ChartOfAccount::class);
        $this->backfillResult = $backfill->backfillForCompany(auth()->user()->company_id);
        $this->showBackfillResult = true;
    }

    public function openCreate(): void
    {
        $this->authorize('create', ChartOfAccount::class);
        $this->reset(['code', 'name', 'type']);
        $this->type = 'asset';
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->authorize('create', ChartOfAccount::class);
        $data = $this->validate();

        ChartOfAccount::create($data + ['company_id' => auth()->user()->company_id]);

        $this->showModal = false;
        $this->reset(['code', 'name', 'type']);
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetErrorBag();
    }

    public function render()
    {
        $user = auth()->user();
        $accounts = ChartOfAccount::where('is_active', true)->orderBy('code')->get();

        return view('livewire.chart-of-account.manage', [
            'accountsByType' => $accounts->groupBy('type'),
            'hasAnyAccounts' => $accounts->isNotEmpty(),
            'canCreate' => $user->can('create', ChartOfAccount::class),
            'canViewCost' => $user->hasPermission('cost.view'),
        ]);
    }
}
