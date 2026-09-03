<?php

namespace App\Services\Accounting;

use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Collection;

/**
 * Extracted from AccountingReportController (Fase L3/L4) so the exact
 * same, hard-won-correct logic can be reused by the new "Laporan" web
 * page (Phase 9) without duplicating it — this logic went through
 * several rounds of bug fixes (a date-comparison bug that needed the
 * explicit two-step JournalEntry-id-then-line-sum pattern below,
 * confirmed working via extensive testing), so it's deliberately kept
 * in ONE place rather than risking re-introducing that bug in a
 * second copy.
 */
class AccountingReportService
{
    public function trialBalance(int $companyId): array
    {
        $accounts = ChartOfAccount::where('company_id', $companyId)->where('is_active', true)->orderBy('code')->get();

        $rows = $accounts->map(function (ChartOfAccount $account) {
            $totalDebit = (float) $account->lines()->sum('debit');
            $totalCredit = (float) $account->lines()->sum('credit');
            $net = round($totalDebit - $totalCredit, 2);

            return [
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'debit' => $net > 0 ? $net : 0.0,
                'credit' => $net < 0 ? round(abs($net), 2) : 0.0,
            ];
        })->filter(fn ($row) => $row['debit'] > 0 || $row['credit'] > 0)->values();

        $totalDebit = round($rows->sum('debit'), 2);
        $totalCredit = round($rows->sum('credit'), 2);

        return [
            'accounts' => $rows,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'is_balanced' => abs($totalDebit - $totalCredit) < 0.01,
        ];
    }

    public function balanceSheet(int $companyId, string $asOf): array
    {
        $entryIds = JournalEntry::where('company_id', $companyId)
            ->whereDate('date', '<=', $asOf)
            ->pluck('id');

        $balanceAsOf = function (ChartOfAccount $account) use ($entryIds) {
            $totalDebit = (float) JournalEntryLine::where('chart_of_account_id', $account->id)
                ->whereIn('journal_entry_id', $entryIds)->sum('debit');
            $totalCredit = (float) JournalEntryLine::where('chart_of_account_id', $account->id)
                ->whereIn('journal_entry_id', $entryIds)->sum('credit');

            return in_array($account->type, ['asset', 'expense'])
                ? round($totalDebit - $totalCredit, 2)
                : round($totalCredit - $totalDebit, 2);
        };

        $accounts = ChartOfAccount::where('company_id', $companyId)->where('is_active', true)->orderBy('code')->get();

        $mapGroup = fn (string $type) => $accounts->where('type', $type)
            ->map(fn ($a) => ['code' => $a->code, 'name' => $a->name, 'balance' => $balanceAsOf($a)])
            ->filter(fn ($row) => $row['balance'] != 0)
            ->values();

        $assets = $mapGroup('asset');
        $liabilities = $mapGroup('liability');
        $equity = $mapGroup('equity');

        $totalRevenue = (float) $accounts->where('type', 'revenue')->sum($balanceAsOf);
        $totalExpense = (float) $accounts->where('type', 'expense')->sum($balanceAsOf);
        $currentEarnings = round($totalRevenue - $totalExpense, 2);

        if ($currentEarnings != 0) {
            $equity->push(['code' => null, 'name' => 'Laba Tahun Berjalan (belum ditutup)', 'balance' => $currentEarnings]);
        }

        $totalAssets = round($assets->sum('balance'), 2);
        $totalLiabilities = round($liabilities->sum('balance'), 2);
        $totalEquity = round($equity->sum('balance'), 2);

        return [
            'as_of' => $asOf,
            'assets' => $assets,
            'total_assets' => $totalAssets,
            'liabilities' => $liabilities,
            'total_liabilities' => $totalLiabilities,
            'equity' => $equity,
            'total_equity' => $totalEquity,
            'is_balanced' => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01,
        ];
    }

    public function incomeStatement(int $companyId, string $from, string $to): array
    {
        $entryIds = JournalEntry::where('company_id', $companyId)
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->pluck('id');

        $periodBalance = function (ChartOfAccount $account) use ($entryIds) {
            $totalDebit = (float) JournalEntryLine::where('chart_of_account_id', $account->id)
                ->whereIn('journal_entry_id', $entryIds)->sum('debit');
            $totalCredit = (float) JournalEntryLine::where('chart_of_account_id', $account->id)
                ->whereIn('journal_entry_id', $entryIds)->sum('credit');

            return $account->type === 'revenue'
                ? round($totalCredit - $totalDebit, 2)
                : round($totalDebit - $totalCredit, 2);
        };

        $accounts = ChartOfAccount::where('company_id', $companyId)->where('is_active', true)->orderBy('code')->get();

        $revenue = $accounts->where('type', 'revenue')
            ->map(fn ($a) => ['code' => $a->code, 'name' => $a->name, 'amount' => $periodBalance($a)])
            ->filter(fn ($row) => $row['amount'] != 0)->values();

        $expenses = $accounts->where('type', 'expense')
            ->map(fn ($a) => ['code' => $a->code, 'name' => $a->name, 'amount' => $periodBalance($a)])
            ->filter(fn ($row) => $row['amount'] != 0)->values();

        $totalRevenue = round($revenue->sum('amount'), 2);
        $totalExpenses = round($expenses->sum('amount'), 2);

        return [
            'from' => $from,
            'to' => $to,
            'revenue' => $revenue,
            'total_revenue' => $totalRevenue,
            'expenses' => $expenses,
            'total_expenses' => $totalExpenses,
            'net_income' => round($totalRevenue - $totalExpenses, 2),
        ];
    }
}
