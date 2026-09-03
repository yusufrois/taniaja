<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByModulePermission;

/**
 * Governs the quick-sale flow only. The base class's create() maps to
 * 'sale.create' — same permission module the full Sales module (Phase 7)
 * will use, since both represent "recording a sale to a customer".
 */
class StockBatchSalePolicy extends AuthorizesByModulePermission
{
    protected string $module = 'sale';
}
