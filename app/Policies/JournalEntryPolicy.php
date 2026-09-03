<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByModulePermission;

/**
 * Same module ('accounting') as ChartOfAccountPolicy — the two are
 * always managed by the same people (Owner/Manager/Finance), so one
 * permission covers both rather than fragmenting into
 * chart_of_account.* and journal_entry.* separately (Aturan #43).
 */
class JournalEntryPolicy extends AuthorizesByModulePermission
{
    protected string $module = 'accounting';
}
