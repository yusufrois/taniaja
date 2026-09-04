<?php

namespace App\Livewire;

use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\Expense;
use App\Models\Greenhouse;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Schedule;
use App\Models\Season;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Main Dashboard — architecture doc Section 24.
 *
 * Access is permission-gated, matching the Role & Permission Matrix
 * (not just "logged in = see everything"): full financial cards/charts
 * need 'report.view' (Owner/Manager/Finance only, same permission Phase
 * 8's report endpoints use). Users without it but with 'activity.view'
 * (Supervisor/Worker) see a smaller operational summary instead. This
 * mirrors how the API itself gates data — the dashboard shouldn't leak
 * company financials to roles that can't see them via the API either.
 */
#[Layout('layouts.app')]
class Dashboard extends Component
{
    public bool $canViewReports = false;
    public bool $canViewActivities = false;

    public float $totalRevenue = 0;
    public float $totalExpense = 0;
    public float $netProfit = 0;
    public float $totalDebtRemaining = 0;
    public int $activeGreenhouses = 0;
    public int $activeSeasons = 0;

    public array $monthlyTrend = [];
    public array $expenseByGreenhouse = [];

    public int $todayActivitiesCount = 0;
    public int $overdueActivitiesCount = 0;

    /**
     * Roadmap tambahan — a warned staff member previously had NO way
     * to see their own warnings anywhere in the UI (only whoever
     * issued it could see it, via Kelola Staf's 'user.warn' gate).
     * This is the user's OWN data, so no extra permission check —
     * everyone can see warnings issued to themselves, same as
     * everyone can see their own payslip/attendance elsewhere.
     */
    public $myWarnings;

    public function mount(): void
    {
        $user = auth()->user();
        $this->canViewReports = $user->hasPermission('report.view');
        $this->canViewActivities = $user->hasPermission('activity.view');
        // Roadmap tambahan — only warnings NOT YET fully confirmed by
        // the issuing atasan appear here. Once acknowledged (staff
        // clicks "Sudah Baca") AND confirmed (atasan approves via
        // Kelola Staf), it stops showing — the 2-step flow is what
        // actually keeps this from "memenuhi dashboard" long-term,
        // not a delete.
        $this->myWarnings = $user->warnings()->whereNull('confirmed_at')->orderByDesc('id')->limit(5)->get();

        if ($this->canViewReports) {
            $this->loadFinancialSummary();
            $this->loadMonthlyTrend();
            $this->loadExpenseByGreenhouse();
        } elseif ($this->canViewActivities) {
            $this->loadActivitySummary();
        }
    }

    /**
     * Net Profit here uses the SAME definition established in Phase 8
     * (Revenue - COGS, where COGS = sum(SaleItem.cost)) — never
     * re-derived differently on the dashboard, so this card can never
     * contradict the formal P&L report for the same period.
     */
    private function loadFinancialSummary(): void
    {
        $this->totalRevenue = (float) Sale::sum('total');
        $this->totalExpense = (float) Expense::whereNotNull('approved_at')->sum('amount');

        $cogs = (float) SaleItem::sum('cost');
        $this->netProfit = round($this->totalRevenue - $cogs, 2);

        $totalDebtAmount = (float) Debt::sum('amount');
        $totalDebtPaid = (float) DebtPayment::sum('amount');
        $this->totalDebtRemaining = max(0, round($totalDebtAmount - $totalDebtPaid, 2));

        $this->activeGreenhouses = Greenhouse::where('status', 'active')->count();
        $this->activeSeasons = Season::whereIn('status', ['planning', 'active', 'harvesting'])->count();
    }

    private function loadMonthlyTrend(): void
    {
        $months = [];

        for ($i = 5; $i >= 0; $i--) {
            $start = Carbon::now()->subMonths($i)->startOfMonth();
            $end = Carbon::now()->subMonths($i)->endOfMonth();

            $revenue = (float) Sale::whereBetween('date', [$start, $end])->sum('total');
            $cogs = (float) SaleItem::whereHas(
                'sale', fn ($q) => $q->whereBetween('date', [$start, $end])
            )->sum('cost');
            $expense = (float) Expense::whereNotNull('approved_at')->whereBetween('date', [$start, $end])->sum('amount');

            $months[] = [
                'label' => $start->translatedFormat('M Y'),
                'revenue' => $revenue,
                'expense' => $expense,
                'gross_profit' => round($revenue - $cogs, 2),
            ];
        }

        $this->monthlyTrend = $months;
    }

    /**
     * Top 5 active greenhouses by total expense. Uses the SAME wrapped
     * where(function(){...->orWhereHas...}) pattern established in
     * ReportController::greenhousePerformance() (Phase 8) — an
     * unwrapped ->where()->orWhereHas() would let the OR condition
     * escape the BelongsToCompany tenant scope, which is a real bug
     * class documented there.
     */
    private function loadExpenseByGreenhouse(): void
    {
        $greenhouses = Greenhouse::where('status', 'active')->get(['id', 'code']);

        $rows = $greenhouses->map(function ($greenhouse) {
            $total = Expense::whereNotNull('approved_at')->where(function ($query) use ($greenhouse) {
                $query->where('greenhouse_id', $greenhouse->id)
                    ->orWhereHas('season', fn ($q) => $q->where('greenhouse_id', $greenhouse->id));
            })->sum('amount');

            return ['label' => $greenhouse->code, 'total' => (float) $total];
        })->sortByDesc('total')->take(5)->values();

        $this->expenseByGreenhouse = $rows->all();
    }

    private function loadActivitySummary(): void
    {
        $schedules = Schedule::whereIn('status', ['pending', 'in_progress'])->get();

        $this->todayActivitiesCount = $schedules->filter(fn ($s) => $s->scheduled_date->isToday())->count();
        $this->overdueActivitiesCount = $schedules->filter(fn ($s) => $s->effective_status === 'overdue')->count();
    }

    /** Roadmap tambahan — "Sudah Baca" step 1 of 2; still shows until atasan confirms too. */
    public function acknowledgeWarning(int $warningId): void
    {
        $warning = auth()->user()->warnings()->findOrFail($warningId);
        $warning->update(['acknowledged_at' => now()]);

        // Roadmap tambahan — bug report #2: atasan had to manually
        // check Kelola Staf to notice a staff member acknowledged.
        // Now sends the issuer a Notification too.
        if ($warning->issued_by) {
            \App\Models\Notification::create([
                'company_id' => $warning->company_id,
                'user_id' => $warning->issued_by,
                'type' => 'warning_acknowledged',
                'title' => auth()->user()->name.' sudah membaca peringatan',
                'body' => 'Menunggu konfirmasi Anda di Kelola Staf.',
                'data' => ['warning_id' => $warning->id, 'user_id' => auth()->id()],
            ]);
        }

        $this->myWarnings = auth()->user()->warnings()->whereNull('confirmed_at')->orderByDesc('id')->limit(5)->get();
    }

    public function render()
    {
        return view('livewire.dashboard');
    }
}
