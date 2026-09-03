<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByModulePermission;

/**
 * Reuses the SAME 'sale' module as StockBatchSalePolicy (Phase 6) — one
 * permission governs both the quick-sell flow and this full invoiced
 * Sale flow, since to the business they're the same action ("recording
 * a sale"), just different levels of detail.
 */
class SalePolicy extends AuthorizesByModulePermission
{
    protected string $module = 'sale';
}
