<?php

namespace App\Livewire\Report;

use App\Models\ChartOfAccount;
use App\Services\Accounting\AccountingReportService;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Uses AccountingReportService directly (Eloquent-style, matching
 * every other Phase 9 page) — NOT an internal HTTP call to
 * AccountingReportController — while still sharing the EXACT SAME
 * computation logic as the API, since both now call the same service.
 */
#[Layout('layouts.app')]
class Manage extends Component
{
    public string $activeTab = 'income-statement';

    public string $from = '';
    public string $to = '';
    public string $asOf = '';

    public function mount(): void
    {
        $this->authorize('viewAny', ChartOfAccount::class);
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->toDateString();
        $this->asOf = now()->toDateString();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function render(AccountingReportService $service)
    {
        $companyId = auth()->user()->company_id;

        return view('livewire.report.manage', [
            'incomeStatement' => $this->activeTab === 'income-statement'
                ? $service->incomeStatement($companyId, $this->from, $this->to) : null,
            'balanceSheet' => $this->activeTab === 'balance-sheet'
                ? $service->balanceSheet($companyId, $this->asOf) : null,
            'trialBalance' => $this->activeTab === 'trial-balance'
                ? $service->trialBalance($companyId) : null,
        ]);
    }
}
