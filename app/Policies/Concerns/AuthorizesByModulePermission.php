<?php

namespace App\Policies\Concerns;

use App\Models\User;

/**
 * Shared authorization logic for master-data and transactional policies.
 *
 * Each concrete policy just sets $module (must match the `module` column
 * seeded in RolePermissionSeeder, e.g. "greenhouse", "crop", "supplier").
 * Permission names follow the convention "{module}.{action}", e.g.
 * "greenhouse.create", "crop.delete".
 *
 * Tenant isolation itself is NOT this class's job — that is already
 * guaranteed by the BelongsToCompany global scope on the model, so a
 * user can never even load a record belonging to another company here.
 * This class only decides WHAT an already-scoped user is allowed to do.
 *
 * "Own data only" (RBAC roadmap Fase A): a module MAY also have a
 * "{module}.view_own" permission — a lesser privilege than
 * "{module}.view" (which grants seeing every record). A user with only
 * view_own can see records they personally created (model.created_by
 * === their id), never other people's.
 *
 * "Team/subordinate data" (RBAC roadmap Fase A2 — "Manager bisa lihat
 * data semua timnya", "SPV bisa lihat bawahannya"): a module MAY also
 * have a "{module}.view_team" permission, sitting BETWEEN view and
 * view_own. A user with only view_team can see records created by
 * themselves OR any of their direct subordinates (User::teamUserIds()).
 * Precedence when a user holds more than one of these is always
 * view > view_team > view_own (see User::canViewTeamOnlyOf() and
 * canViewOwnOnlyOf(), which each exclude the tiers above them).
 *
 * view() enforces all of this per-record; viewAny() only confirms the
 * user has SOME viewing right at all — it's the controller's job to
 * actually scope the list query correctly (see
 * User::canViewAllOf()/canViewTeamOnlyOf()/canViewOwnOnlyOf() and their
 * usage in PurchaseController/SaleController/ExpenseController).
 * Models using this must have a `created_by` column; a module that never
 * seeds "{module}.view_own"/"{module}.view_team" behaves exactly as
 * before (full view or nothing).
 */
abstract class AuthorizesByModulePermission
{
    protected string $module;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission("{$this->module}.view")
            || $user->hasPermission("{$this->module}.view_team")
            || $user->hasPermission("{$this->module}.view_own");
    }

    public function view(User $user, $model): bool
    {
        if ($user->hasPermission("{$this->module}.view")) {
            return true;
        }

        if ($user->hasPermission("{$this->module}.view_team")) {
            return isset($model->created_by)
                && in_array($model->created_by, $user->teamUserIds(), true);
        }

        if ($user->hasPermission("{$this->module}.view_own")) {
            return isset($model->created_by) && $model->created_by === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission("{$this->module}.create");
    }

    public function update(User $user, $model): bool
    {
        return $user->hasPermission("{$this->module}.update");
    }

    public function delete(User $user, $model): bool
    {
        return $user->hasPermission("{$this->module}.delete");
    }
}
