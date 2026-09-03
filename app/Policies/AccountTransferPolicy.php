<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByModulePermission;

/**
 * Reuses the 'accounting' module (same as ChartOfAccount/JournalEntry,
 * Fase L1) — moving money between the company's own Kas/Bank accounts
 * is squarely part of the accounting domain, same people manage it.
 */
class AccountTransferPolicy extends AuthorizesByModulePermission
{
    protected string $module = 'accounting';
}
