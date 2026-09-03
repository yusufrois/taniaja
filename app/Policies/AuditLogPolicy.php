<?php

namespace App\Policies;

use App\Models\User;

/**
 * Custom (not AuthorizesByModulePermission) — AuditLog has no
 * created_by/view_own concept; it's a system-wide trail for the whole
 * company, not per-user records. Just a flat viewAny() check.
 */
class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('audit.view');
    }
}
