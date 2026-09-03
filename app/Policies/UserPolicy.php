<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByModulePermission;

/**
 * Governs an Owner (or whoever is granted 'user.*' permissions)
 * managing OTHER users within their own company — inviting staff,
 * assigning roles/supervisors, deactivating accounts. Uses the same
 * shared base as every other module; User has no `created_by` column
 * so the base class's view_own/view_team branches simply never match
 * here (harmless — 'user.view_own'/'user.view_team' are never seeded).
 */
class UserPolicy extends AuthorizesByModulePermission
{
    protected string $module = 'user';
}
