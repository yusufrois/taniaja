<?php

namespace App\Providers;

use App\Models\CapitalTransaction;
use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\Expense;
use App\Models\Purchase;
use App\Models\PurchasePayment;
use App\Models\Sale;
use App\Models\Asset;
use App\Models\SalePayment;
use App\Models\StockBatchSale;
use App\Models\AccountTransfer;
use App\Observers\CapitalTransactionObserver;
use App\Observers\DebtObserver;
use App\Observers\DebtPaymentObserver;
use App\Observers\ExpenseObserver;
use App\Observers\PurchaseObserver;
use App\Observers\PurchasePaymentObserver;
use App\Observers\SaleObserver;
use App\Observers\AssetObserver;
use App\Observers\SalePaymentObserver;
use App\Observers\StockBatchSaleObserver;
use App\Observers\AccountTransferObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * Roadmap Fase L2 — registers the Observers that turn transactions
     * into automatic journal entries. See AccountingPostingService for
     * the actual posting logic.
     *
     * NOTE: Sale is deliberately NOT observed for 'created' here — its
     * accounting post depends on SaleItem.cost (for the COGS split),
     * and SaleItems don't exist yet at the moment Sale's 'created'
     * event fires (they're created in a loop AFTER the Sale row
     * itself, inside SalesService). Sale posting is instead called
     * EXPLICITLY at the end of SalesService::create(), once items are
     * done. Using an Observer there would silently post an incomplete
     * (COGS-less) journal — this was caught by AutoPostingTest's COGS
     * test before being caught any other way.
     *
     * Sale's 'deleted' event does NOT have this timing problem (the
     * journal, if any, already exists by then) — so SaleObserver
     * below only implements deleted(), for the same void-on-delete
     * fix (bug report #7) as every other transaction type.
     */
    public function boot(): void
    {
        SalePayment::observe(SalePaymentObserver::class);
        Sale::observe(SaleObserver::class);
        Asset::observe(AssetObserver::class);
        StockBatchSale::observe(StockBatchSaleObserver::class);
        Purchase::observe(PurchaseObserver::class);
        PurchasePayment::observe(PurchasePaymentObserver::class);
        Expense::observe(ExpenseObserver::class);
        Debt::observe(DebtObserver::class);
        DebtPayment::observe(DebtPaymentObserver::class);
        CapitalTransaction::observe(CapitalTransactionObserver::class);
        AccountTransfer::observe(AccountTransferObserver::class);
    }
}
