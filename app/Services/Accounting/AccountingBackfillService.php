<?php

namespace App\Services\Accounting;

use App\Models\CapitalTransaction;
use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\Expense;
use App\Models\JournalEntry;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\AccountTransfer;
use App\Models\Asset;
use App\Models\StockBatchSale;

/**
 * Roadmap tambahan (real-world gap discovered by the person) — a
 * company that recorded transactions BEFORE ever setting up its
 * Chart of Accounts (Fase L1) has Expense/Debt/Purchase/etc. rows
 * that exist perfectly fine, but NEVER got a journal entry, since
 * AccountingPostingService's graceful-degradation design (Fase L2)
 * deliberately lets the underlying transaction succeed even when
 * posting silently fails from missing accounts. Those old records
 * are correct as-is, but invisible to Laporan (which is 100%
 * ledger-based) until someone re-runs posting for them — that's
 * exactly what this does, safely and idempotently.
 */
class AccountingBackfillService
{
    public function __construct(private AccountingPostingService $posting) {}

    /**
     * @return array<string,int> how many records of each type got
     *   newly posted (0 means everything for that type was already
     *   posted, or there's nothing of that type at all).
     */
    public function backfillForCompany(int $companyId): array
    {
        return [
            'expense' => $this->backfillModel(Expense::class, 'expense', $companyId, fn ($m) => $this->posting->postExpense($m)),
            'purchase' => $this->backfillModel(Purchase::class, 'purchase', $companyId, fn ($m) => $this->posting->postPurchase($m)),
            'purchase_payment' => $this->backfillModel(PurchasePayment::class, 'purchase_payment', $companyId, fn ($m) => $this->posting->postPurchasePayment($m)),
            'sale' => $this->backfillModel(Sale::class, 'sale', $companyId, fn ($m) => $this->posting->postSale($m)),
            'sale_payment' => $this->backfillModel(SalePayment::class, 'sale_payment', $companyId, fn ($m) => $this->posting->postSalePayment($m)),
            'stock_batch_sale' => $this->backfillModel(StockBatchSale::class, 'stock_batch_sale', $companyId, fn ($m) => $this->posting->postStockBatchSale($m)),
            'debt' => $this->backfillModel(Debt::class, 'debt', $companyId, fn ($m) => $this->posting->postDebt($m)),
            'debt_payment' => $this->backfillModel(DebtPayment::class, 'debt_payment', $companyId, fn ($m) => $this->posting->postDebtPayment($m)),
            'capital_transaction' => $this->backfillModel(CapitalTransaction::class, 'capital_transaction', $companyId, fn ($m) => $this->posting->postCapitalTransaction($m)),
            'account_transfer' => $this->backfillModel(AccountTransfer::class, 'account_transfer', $companyId, fn ($m) => $this->posting->postAccountTransfer($m)),
            'asset' => $this->backfillModel(Asset::class, 'asset', $companyId, fn ($m) => $this->posting->postAsset($m)),
        ];
    }

    /**
     * @param  class-string  $modelClass
     */
    private function backfillModel(string $modelClass, string $referenceType, int $companyId, \Closure $poster): int
    {
        $alreadyPostedIds = JournalEntry::where('company_id', $companyId)
            ->where('reference_type', $referenceType)
            ->pluck('reference_id');

        $unposted = $modelClass::where('company_id', $companyId)
            ->whereNotIn('id', $alreadyPostedIds)
            ->get();

        foreach ($unposted as $model) {
            $poster($model);
        }

        return $unposted->count();
    }
}
