<?php

namespace App\Services\Accounting;

use App\Models\AccountTransfer;
use App\Models\ChartOfAccount;
use App\Models\CapitalTransaction;
use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\Expense;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\StockBatchSale;
use App\Models\User;
use App\Models\Asset;
use App\Models\JournalEntry;
use Illuminate\Support\Facades\Log;

/**
 * Roadmap Fase L2 — turns existing transactions (Sale, Purchase,
 * Expense, Debt, Capital — all pre-existing since Phase 5-7) into
 * automatic journal entries, using the SAME JournalEntryService that
 * enforces the balanced-double-entry rule (never bypassed).
 *
 * Roadmap Fase L5 — "setiap pembayaran WAJIB pilih dari akun mana
 * uangnya masuk/keluar". Wherever a model now HAS a
 * chart_of_account_id (SalePayment, PurchasePayment, Expense, Debt,
 * DebtPayment, CapitalTransaction), that SPECIFIC account is used for
 * the cash side of the journal, via cashAccount(). If not specified
 * (nullable — backward compatible with data/tests from before L5),
 * falls back to the default Kas (1100), same as always.
 *
 * CRITICAL DESIGN DECISION (unchanged from L2): every post*() method
 * wraps its work in a try/catch and NEVER lets an exception escape —
 * see cashAccount()/account() and the safely() wrapper. A missing
 * Chart of Accounts (or a payment referencing a deleted custom
 * account) must never block the underlying business transaction.
 */
class AccountingPostingService
{
    public function __construct(private JournalEntryService $journal) {}

    public function postSale(Sale $sale): void
    {
        $this->safely($sale->company_id, 'Sale', $sale->id, function () use ($sale) {
            $companyId = $sale->company_id;
            $piutang = $this->account($companyId, '1200');
            $pendapatan = $this->account($companyId, '4100');

            $lines = [
                ['chart_of_account_id' => $piutang->id, 'debit' => (float) $sale->total, 'credit' => 0, 'notes' => "Piutang {$sale->invoice_number}"],
                ['chart_of_account_id' => $pendapatan->id, 'debit' => 0, 'credit' => (float) $sale->total, 'notes' => "Pendapatan {$sale->invoice_number}"],
            ];

            $cogs = (float) $sale->items()->sum('cost');

            if ($cogs > 0) {
                $hpp = $this->account($companyId, '5100');
                $persediaan = $this->account($companyId, '1300');
                $lines[] = ['chart_of_account_id' => $hpp->id, 'debit' => $cogs, 'credit' => 0, 'notes' => 'HPP'];
                $lines[] = ['chart_of_account_id' => $persediaan->id, 'debit' => 0, 'credit' => $cogs, 'notes' => 'Pengurangan persediaan'];
            }

            $this->journal->create(
                ['date' => $sale->date, 'description' => "Penjualan {$sale->invoice_number}", 'reference_type' => 'sale', 'reference_id' => $sale->id],
                $lines,
                $this->systemUser($sale->created_by, $companyId)
            );
        });
    }

    public function postSalePayment(SalePayment $payment): void
    {
        $this->safely($payment->company_id, 'SalePayment', $payment->id, function () use ($payment) {
            $companyId = $payment->company_id;
            $kas = $this->cashAccount($payment->chart_of_account_id, $companyId);
            $piutang = $this->account($companyId, '1200');

            $this->journal->create(
                ['date' => $payment->payment_date, 'description' => 'Pembayaran piutang dari customer', 'reference_type' => 'sale_payment', 'reference_id' => $payment->id],
                [
                    ['chart_of_account_id' => $kas->id, 'debit' => (float) $payment->amount, 'credit' => 0],
                    ['chart_of_account_id' => $piutang->id, 'debit' => 0, 'credit' => (float) $payment->amount],
                ],
                $this->systemUser(null, $companyId)
            );
        });
    }

    /**
     * Phase 6's "quick sale" flow — still assumes the default Kas
     * (1100); NOT extended to a selectable account in this pass
     * (StockBatchSale has no chart_of_account_id column) — see
     * README-FASE-L5 for why this was deliberately left out of scope.
     */
    public function postStockBatchSale(StockBatchSale $sale): void
    {
        $this->safely($sale->company_id, 'StockBatchSale', $sale->id, function () use ($sale) {
            $companyId = $sale->company_id;
            $kas = $this->account($companyId, '1100');
            $pendapatan = $this->account($companyId, '4100');

            $lines = [
                ['chart_of_account_id' => $kas->id, 'debit' => (float) $sale->revenue, 'credit' => 0],
                ['chart_of_account_id' => $pendapatan->id, 'debit' => 0, 'credit' => (float) $sale->revenue],
            ];

            $cogs = (float) $sale->cost;

            if ($cogs > 0) {
                $hpp = $this->account($companyId, '5100');
                $persediaan = $this->account($companyId, '1300');
                $lines[] = ['chart_of_account_id' => $hpp->id, 'debit' => $cogs, 'credit' => 0, 'notes' => 'HPP'];
                $lines[] = ['chart_of_account_id' => $persediaan->id, 'debit' => 0, 'credit' => $cogs, 'notes' => 'Pengurangan persediaan'];
            }

            $this->journal->create(
                ['date' => $sale->sale_date, 'description' => "Jual cepat #{$sale->id}", 'reference_type' => 'stock_batch_sale', 'reference_id' => $sale->id],
                $lines,
                $this->systemUser($sale->created_by, $companyId)
            );
        });
    }

    public function postPurchase(Purchase $purchase): void
    {
        $this->safely($purchase->company_id, 'Purchase', $purchase->id, function () use ($purchase) {
            $companyId = $purchase->company_id;
            $persediaan = $this->account($companyId, '1300');
            $hutang = $this->account($companyId, '2100');

            $this->journal->create(
                ['date' => $purchase->purchase_date, 'description' => "Pembelian dari petani #{$purchase->id}", 'reference_type' => 'purchase', 'reference_id' => $purchase->id],
                [
                    ['chart_of_account_id' => $persediaan->id, 'debit' => (float) $purchase->total_amount, 'credit' => 0],
                    ['chart_of_account_id' => $hutang->id, 'debit' => 0, 'credit' => (float) $purchase->total_amount],
                ],
                $this->systemUser($purchase->created_by, $companyId)
            );
        });
    }

    public function postPurchasePayment(PurchasePayment $payment): void
    {
        $this->safely($payment->company_id, 'PurchasePayment', $payment->id, function () use ($payment) {
            $companyId = $payment->company_id;
            $hutang = $this->account($companyId, '2100');
            $kas = $this->cashAccount($payment->chart_of_account_id, $companyId);

            $this->journal->create(
                ['date' => $payment->payment_date, 'description' => 'Pembayaran hutang ke petani/supplier', 'reference_type' => 'purchase_payment', 'reference_id' => $payment->id],
                [
                    ['chart_of_account_id' => $hutang->id, 'debit' => (float) $payment->amount, 'credit' => 0],
                    ['chart_of_account_id' => $kas->id, 'debit' => 0, 'credit' => (float) $payment->amount],
                ],
                $this->systemUser(null, $companyId)
            );
        });
    }

    /**
     * Fase L5: cash side now uses $expense->chart_of_account_id when
     * set (falls back to default Kas otherwise). Still assumes the
     * expense is paid immediately (no separate payable tracking, same
     * simplification as before L5). Also fires for InputUsage's
     * auto-created Expenses (Fase C revision) since those go through
     * the same Expense::create() path.
     */
    public function postExpense(Expense $expense): void
    {
        $this->safely($expense->company_id, 'Expense', $expense->id, function () use ($expense) {
            $companyId = $expense->company_id;
            $beban = $this->account($companyId, '5200');
            $kas = $this->cashAccount($expense->chart_of_account_id, $companyId);

            $this->journal->create(
                ['date' => $expense->date, 'description' => $expense->description ?? "Beban Operasional #{$expense->id}", 'reference_type' => 'expense', 'reference_id' => $expense->id],
                [
                    ['chart_of_account_id' => $beban->id, 'debit' => (float) $expense->amount, 'credit' => 0],
                    ['chart_of_account_id' => $kas->id, 'debit' => 0, 'credit' => (float) $expense->amount],
                ],
                $this->systemUser($expense->created_by, $companyId)
            );
        });
    }

    public function postDebt(Debt $debt): void
    {
        $this->safely($debt->company_id, 'Debt', $debt->id, function () use ($debt) {
            $companyId = $debt->company_id;
            $kas = $this->cashAccount($debt->chart_of_account_id, $companyId);
            $hutangBank = $this->account($companyId, '2200');

            $this->journal->create(
                ['date' => $debt->debt_date, 'description' => "Pinjaman dari {$debt->creditor_name}", 'reference_type' => 'debt', 'reference_id' => $debt->id],
                [
                    ['chart_of_account_id' => $kas->id, 'debit' => (float) $debt->amount, 'credit' => 0],
                    ['chart_of_account_id' => $hutangBank->id, 'debit' => 0, 'credit' => (float) $debt->amount],
                ],
                $this->systemUser(null, $companyId)
            );
        });
    }

    public function postDebtPayment(DebtPayment $payment): void
    {
        $this->safely($payment->company_id, 'DebtPayment', $payment->id, function () use ($payment) {
            $companyId = $payment->company_id;
            $hutangBank = $this->account($companyId, '2200');
            $kas = $this->cashAccount($payment->chart_of_account_id, $companyId);

            $this->journal->create(
                ['date' => $payment->payment_date, 'description' => 'Pembayaran cicilan pinjaman', 'reference_type' => 'debt_payment', 'reference_id' => $payment->id],
                [
                    ['chart_of_account_id' => $hutangBank->id, 'debit' => (float) $payment->amount, 'credit' => 0],
                    ['chart_of_account_id' => $kas->id, 'debit' => 0, 'credit' => (float) $payment->amount],
                ],
                $this->systemUser(null, $companyId)
            );
        });
    }

    /**
     * 'withdrawal' (prive) flips the debit/credit sides compared to an
     * injection — money leaves the business back to the owner.
     */
    public function postCapitalTransaction(CapitalTransaction $transaction): void
    {
        $this->safely($transaction->company_id, 'CapitalTransaction', $transaction->id, function () use ($transaction) {
            $companyId = $transaction->company_id;
            $kas = $this->cashAccount($transaction->chart_of_account_id, $companyId);
            $modal = $this->account($companyId, '3100');
            $amount = (float) $transaction->amount;

            $lines = $transaction->type === 'withdrawal'
                ? [
                    ['chart_of_account_id' => $modal->id, 'debit' => $amount, 'credit' => 0, 'notes' => 'Prive/penarikan modal'],
                    ['chart_of_account_id' => $kas->id, 'debit' => 0, 'credit' => $amount],
                ]
                : [
                    ['chart_of_account_id' => $kas->id, 'debit' => $amount, 'credit' => 0],
                    ['chart_of_account_id' => $modal->id, 'debit' => 0, 'credit' => $amount, 'notes' => 'Setoran modal'],
                ];

            $this->journal->create(
                ['date' => $transaction->date, 'description' => 'Transaksi modal #'.$transaction->id, 'reference_type' => 'capital_transaction', 'reference_id' => $transaction->id],
                $lines,
                $this->systemUser($transaction->created_by, $companyId)
            );
        });
    }

    /**
     * Roadmap tambahan #6 — "saat input aset tetap apakah otomatis
     * mencatat pengeluaran dan mengurangi saldo". Debit Aset Tetap
     * (1400), Credit Kas/Bank — same shape as a Capital withdrawal's
     * cash side, just the other account is Aset Tetap instead of
     * Beban. Uses cashAccount() so a specific Kas/Bank account can be
     * chosen, same as Expense/Debt/Capital (Fase L5).
     */
    public function postAsset(Asset $asset): void
    {
        $this->safely($asset->company_id, 'Asset', $asset->id, function () use ($asset) {
            $companyId = $asset->company_id;
            $assetAccount = $this->account($companyId, '1400');
            $kas = $this->cashAccount($asset->chart_of_account_id, $companyId);

            $this->journal->create(
                ['date' => $asset->purchase_date, 'description' => "Pembelian aset: {$asset->name}", 'reference_type' => 'asset', 'reference_id' => $asset->id],
                [
                    ['chart_of_account_id' => $assetAccount->id, 'debit' => (float) $asset->value, 'credit' => 0],
                    ['chart_of_account_id' => $kas->id, 'debit' => 0, 'credit' => (float) $asset->value],
                ],
                $this->systemUser(null, $companyId)
            );
        });
    }

    /**
     * Roadmap Fase L5 — "Transfer antar akun (Kas ke Bank, dst) — juga
     * jadi jurnal". Moving money between the company's OWN accounts
     * doesn't change total assets — simple Debit-to / Credit-from.
     */
    public function postAccountTransfer(AccountTransfer $transfer): void
    {
        $this->safely($transfer->company_id, 'AccountTransfer', $transfer->id, function () use ($transfer) {
            $amount = (float) $transfer->amount;

            $this->journal->create(
                ['date' => $transfer->date, 'description' => $transfer->notes ?? "Transfer antar akun #{$transfer->id}", 'reference_type' => 'account_transfer', 'reference_id' => $transfer->id],
                [
                    ['chart_of_account_id' => $transfer->to_account_id, 'debit' => $amount, 'credit' => 0],
                    ['chart_of_account_id' => $transfer->from_account_id, 'debit' => 0, 'credit' => $amount],
                ],
                $this->systemUser($transfer->created_by, $transfer->company_id)
            );
        });
    }

    /**
     * Resolves which Kas/Bank account a cash-side journal line should
     * use: the SPECIFIC account chosen on the source record if set,
     * otherwise the default Kas (1100) — exactly as before Fase L5.
     */
    private function cashAccount(?int $chartOfAccountId, int $companyId): ChartOfAccount
    {
        if ($chartOfAccountId) {
            $account = ChartOfAccount::where('company_id', $companyId)->find($chartOfAccountId);
            if ($account) {
                return $account;
            }
        }

        return $this->account($companyId, '1100');
    }

    private function account(int $companyId, string $code): ChartOfAccount
    {
        return ChartOfAccount::where('company_id', $companyId)->where('code', $code)->firstOrFail();
    }

    /**
     * JournalEntryService::create() needs a User (for created_by /
     * company_id) — reuses the transaction's own creator when known,
     * otherwise falls back to any user in the company (observers for
     * *Payment models don't always have a created_by on the payment
     * itself). This is only used to STAMP who/which company the
     * auto-posted entry belongs to, not for permission checks (auto-
     * posting bypasses the Policy layer entirely, same as any other
     * internal system action).
     */
    private function systemUser(?int $userId, int $companyId): User
    {
        if ($userId) {
            $user = User::find($userId);
            if ($user) {
                return $user;
            }
        }

        return User::where('company_id', $companyId)->firstOrFail();
    }

    private function safely(int $companyId, string $sourceType, int $sourceId, \Closure $callback): void
    {
        try {
            $callback();
        } catch (\Throwable $e) {
            Log::warning("AccountingPostingService: gagal posting jurnal otomatis untuk {$sourceType} #{$sourceId} (company {$companyId}) — kemungkinan Bagan Akun belum di-setup. Transaksi aslinya TETAP berhasil.", [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Roadmap tambahan (bug report pengguna, #7) — menghapus Beban
     * (atau transaksi lain apapun) TIDAK pernah membatalkan jurnalnya,
     * jadi Neraca Saldo/Laporan tetap mencatat angka lama meski
     * transaksinya sudah dihapus. Sama seperti JournalEntryController's
     * void manual (destroy()) — soft-delete baris-barisnya dulu, baru
     * entrinya sendiri, supaya ChartOfAccount::balance() berhenti
     * menghitungnya. Aman dipanggil untuk transaksi yang jurnalnya
     * memang belum pernah ada (mis. tercatat sebelum Bagan Akun
     * di-seed) — findFor() akan null, tidak ada apapun yang terjadi.
     */
    public function voidFor(string $referenceType, int $referenceId): void
    {
        $entry = JournalEntry::where('reference_type', $referenceType)->where('reference_id', $referenceId)->first();

        if (! $entry) {
            return;
        }

        $entry->lines()->delete();
        $entry->delete();
    }
}
