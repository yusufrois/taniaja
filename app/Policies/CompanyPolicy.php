<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

/**
 * Custom (not AuthorizesByModulePermission) — there's only ever ONE
 * Company a user could possibly view/update: their own. No index/
 * create/delete here at all (companies are created via registration,
 * Fase 1; deleted only by a Super Admin process, out of scope here).
 */
class CompanyPolicy
{
    public function view(User $user, Company $company): bool
    {
        return $user->company_id === $company->id && $user->hasPermission('company.view');
    }

    public function update(User $user, Company $company): bool
    {
        return $user->company_id === $company->id && $user->hasPermission('company.update');
    }
}
