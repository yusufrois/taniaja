<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Services\Accounting\AccountingReportService;
use Illuminate\Http\Request;

/**
 * Roadmap Fase L3/L4 — Buku Besar, Neraca Saldo, Neraca, Laba Rugi.
 * trialBalance/balanceSheet/incomeStatement now delegate to
 * AccountingReportService (extracted for Phase 9's "Laporan" web page
 * to reuse the SAME logic — see that class's docblock for why).
 * ledger() stays here as-is (not reused elsewhere yet).
 */
class AccountingReportController extends Controller
{
    public function ledger(Request $request, ChartOfAccount $chart_of_account)
    {
        $this->authorize('view', $chart_of_account);

        $query = $chart_of_account->lines()->with('journalEntry')->orderBy('id');

        if ($request->filled('from')) {
            $from = $request->query('from');
            $query->whereHas('journalEntry', fn ($q) => $q->whereDate('date', '>=', $from));
        }
        if ($request->filled('to')) {
            $to = $request->query('to');
            $query->whereHas('journalEntry', fn ($q) => $q->whereDate('date', '<=', $to));
        }

        $isDebitNormal = in_array($chart_of_account->type, ['asset', 'expense']);
        $running = 0.0;

        $rows = $query->get()->map(function ($line) use (&$running, $isDebitNormal) {
            $debit = (float) $line->debit;
            $credit = (float) $line->credit;
            $delta = $isDebitNormal ? ($debit - $credit) : ($credit - $debit);
            $running = round($running + $delta, 2);

            return [
                'journal_entry_id' => $line->journal_entry_id,
                'date' => $line->journalEntry?->date?->toDateString(),
                'description' => $line->journalEntry?->description,
                'reference_type' => $line->journalEntry?->reference_type,
                'reference_id' => $line->journalEntry?->reference_id,
                'debit' => $debit,
                'credit' => $credit,
                'running_balance' => $running,
            ];
        });

        return response()->json([
            'account' => [
                'id' => $chart_of_account->id,
                'code' => $chart_of_account->code,
                'name' => $chart_of_account->name,
                'type' => $chart_of_account->type,
            ],
            'lines' => $rows,
            'closing_balance' => $running,
        ]);
    }

    public function trialBalance(AccountingReportService $service)
    {
        $this->authorize('viewAny', ChartOfAccount::class);

        return response()->json($service->trialBalance(auth()->user()->company_id));
    }

    public function balanceSheet(Request $request, AccountingReportService $service)
    {
        $this->authorize('viewAny', ChartOfAccount::class);

        $asOf = $request->query('as_of', now()->toDateString());

        return response()->json($service->balanceSheet(auth()->user()->company_id, $asOf));
    }

    public function incomeStatement(Request $request, AccountingReportService $service)
    {
        $this->authorize('viewAny', ChartOfAccount::class);

        $from = $request->query('from', now()->startOfMonth()->toDateString());
        $to = $request->query('to', now()->toDateString());

        return response()->json($service->incomeStatement(auth()->user()->company_id, $from, $to));
    }
}
