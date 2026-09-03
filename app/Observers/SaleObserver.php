<?php

namespace App\Observers;

use App\Models\Sale;
use App\Services\Accounting\AccountingPostingService;

/**
 * Unlike every other transaction type, Sale is NOT observed for
 * 'created' (see AppServiceProvider's docblock — SaleItems don't
 * exist yet when Sale's 'created' event fires, so posting is called
 * explicitly at the end of SalesService::create() instead). That
 * timing problem does NOT apply to 'deleted' — by the time a Sale is
 * deleted, its journal entry (if any) already exists and just needs
 * voiding, same as every other transaction type (bug report #7).
 */
class SaleObserver
{
    public function __construct(private AccountingPostingService $accounting) {}

    public function deleted(Sale $sale): void
    {
        $this->accounting->voidFor('sale', $sale->id);
    }
}
