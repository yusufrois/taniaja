<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByModulePermission;

/**
 * Separate module from InputUsagePolicy on purpose — per the "petani +
 * tim" business scheme, the Owner buys input stock but a Field Officer
 * only ever USES it. Splitting the permission means a Field Officer
 * can be given input_usage.create without ever being able to record
 * (or see the cost of) a purchase.
 */
class InputPurchasePolicy extends AuthorizesByModulePermission
{
    protected string $module = 'input_purchase';
}
