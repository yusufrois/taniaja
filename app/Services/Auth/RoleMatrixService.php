<?php

namespace App\Services\Auth;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;

/**
 * Bug report: "untuk peran cuma ada company owner aja" — root cause
 * found. CompanyRegistrationService only ever created the 'owner'
 * Role record; RoleCapabilitySeeder only ATTACHES permissions to
 * roles that already exist (it never creates missing ones — see its
 * `if (! $rolesBySlug->has($slug)) continue;` guard). So a company
 * registered through the real flow got an Owner role with a name but
 * NO manager/supervisor/finance/worker roles ever came into being —
 * they simply never had a row to attach permissions to.
 *
 * This service is now the single source of truth for "which 5 roles
 * exist, and what does each get" — the Role & Permission Matrix
 * lives HERE (moved from RoleCapabilitySeeder), and BOTH
 * CompanyRegistrationService (new companies) and RoleCapabilitySeeder
 * (backfilling companies that already hit this bug, this app's own
 * demo company included) call provisionRolesForCompany() so neither
 * path can drift out of sync with the other again.
 */
class RoleMatrixService
{
    /** Structure: [role_slug => [module => [actions]]]. */
    public array $matrix = [
        'owner' => [
            'user' => ['view', 'create', 'update', 'delete', 'warn'],
            'greenhouse' => ['view', 'create', 'update', 'delete'],
            'crop' => ['view', 'create', 'update', 'delete'],
            'variety' => ['view', 'create', 'update', 'delete'],
            'supplier' => ['view', 'create', 'update', 'delete'],
            'customer' => ['view', 'create', 'update', 'delete'],
            'grade' => ['view', 'create', 'update', 'delete'],
            'expense_category' => ['view', 'create', 'update', 'delete'],
            'season' => ['view', 'create', 'update', 'delete'],
            'template' => ['view', 'create', 'update', 'delete'],
            'activity' => ['view', 'create', 'update', 'delete'],
            'capital' => ['view', 'create', 'update', 'delete'],
            'asset' => ['view', 'create', 'update', 'delete'],
            'asset_category' => ['view', 'create', 'update', 'delete'],
            'expense' => ['view', 'create', 'update', 'delete', 'approve'],
            'debt' => ['view', 'create', 'update', 'delete'],
            'harvest' => ['view', 'create', 'update', 'delete'],
            'purchase' => ['view', 'create', 'update', 'delete'],
            'sale' => ['view', 'create', 'update', 'delete'],
            'report' => ['view'],
            'plant_loss' => ['view', 'create', 'update', 'delete'],
            'cost' => ['view'],
            'input_item' => ['view', 'create', 'update', 'delete'],
            'input_purchase' => ['view', 'create', 'update', 'delete'],
            'input_usage' => ['view', 'create', 'update', 'delete'],
            'employee' => ['view', 'create', 'update', 'delete'],
            'attendance' => ['view', 'create', 'update', 'delete'],
            'work_type' => ['view', 'create', 'update', 'delete'],
            'piece_work_log' => ['view', 'create', 'update', 'delete'],
            'employee_loan' => ['view', 'create', 'update', 'delete'],
            'payroll' => ['view', 'create', 'update'],
            'task' => ['view', 'create', 'update', 'delete'],
            'delivery_note' => ['view', 'create', 'update', 'delete'],
            'accounting' => ['view', 'create', 'update', 'delete'],
            'company' => ['view', 'update'],
            'audit' => ['view'],
        ],
        'manager' => [
            'greenhouse' => ['view', 'create', 'update', 'delete'],
            'crop' => ['view', 'create', 'update', 'delete'],
            'variety' => ['view', 'create', 'update', 'delete'],
            'supplier' => ['view', 'create', 'update', 'delete'],
            'customer' => ['view', 'create', 'update', 'delete'],
            'grade' => ['view', 'create', 'update', 'delete'],
            'expense_category' => ['view', 'create', 'update', 'delete'],
            'season' => ['view', 'create', 'update', 'delete'],
            'template' => ['view', 'create', 'update', 'delete'],
            'activity' => ['view', 'create', 'update', 'delete'],
            'capital' => ['view'],
            'asset' => ['view'],
            'asset_category' => ['view'],
            'expense' => ['view', 'create', 'update', 'delete', 'approve'],
            'debt' => ['view'],
            'harvest' => ['view', 'create', 'update', 'delete'],
            'purchase' => ['view', 'create', 'update', 'delete'],
            'sale' => ['view', 'create', 'update', 'delete'],
            'report' => ['view'],
            'plant_loss' => ['view', 'create', 'update', 'delete'],
            'cost' => ['view'],
            'input_item' => ['view', 'create', 'update', 'delete'],
            'input_purchase' => ['view', 'create', 'update', 'delete'],
            'input_usage' => ['view', 'create', 'update', 'delete'],
            'employee' => ['view', 'create', 'update', 'delete'],
            'attendance' => ['view', 'create', 'update', 'delete'],
            'work_type' => ['view', 'create', 'update', 'delete'],
            'piece_work_log' => ['view', 'create', 'update', 'delete'],
            'employee_loan' => ['view', 'create', 'update', 'delete'],
            'payroll' => ['view', 'create', 'update'],
            'task' => ['view', 'create', 'update', 'delete'],
            'delivery_note' => ['view', 'create', 'update', 'delete'],
            'accounting' => ['view', 'create', 'update', 'delete'],
        ],
        'supervisor' => [
            'greenhouse' => ['view'],
            'crop' => ['view'],
            'variety' => ['view'],
            'supplier' => ['view'],
            'customer' => ['view'],
            'grade' => ['view'],
            'expense_category' => ['view'],
            'season' => ['view', 'create'],
            'template' => ['view', 'create'],
            'activity' => ['view', 'create'],
            'expense' => ['view', 'create'],
            'harvest' => ['view', 'create'],
            'plant_loss' => ['view', 'create'],
            'input_item' => ['view'],
            'input_usage' => ['view', 'create'],
            'employee' => ['view', 'create'],
            'attendance' => ['view', 'create', 'update'],
            'work_type' => ['view'],
            'piece_work_log' => ['view', 'create'],
            'task' => ['create', 'view_own'],
            'delivery_note' => ['view', 'create'],
        ],
        'finance' => [
            'greenhouse' => ['view'],
            'crop' => ['view'],
            'variety' => ['view'],
            'supplier' => ['view'],
            'customer' => ['view'],
            'grade' => ['view'],
            'expense_category' => ['view'],
            'capital' => ['view', 'create', 'update', 'delete'],
            'asset' => ['view', 'create', 'update', 'delete'],
            'asset_category' => ['view', 'create', 'update', 'delete'],
            'expense' => ['view', 'create', 'update', 'delete', 'approve'],
            'debt' => ['view', 'create', 'update', 'delete'],
            'purchase' => ['view', 'create', 'update', 'delete'],
            'sale' => ['view', 'create', 'update', 'delete'],
            'report' => ['view'],
            'cost' => ['view'],
            'input_item' => ['view'],
            'input_purchase' => ['view'],
            'input_usage' => ['view'],
            'employee' => ['view'],
            'attendance' => ['view'],
            'work_type' => ['view'],
            'piece_work_log' => ['view'],
            'employee_loan' => ['view', 'create', 'update', 'delete'],
            'payroll' => ['view', 'create', 'update'],
            'delivery_note' => ['view'],
            'accounting' => ['view', 'create', 'update', 'delete'],
        ],
        'worker' => [
            'activity' => ['view', 'create'],
            'harvest' => ['view', 'create'],
            'plant_loss' => ['view', 'create'],
            'input_item' => ['view'],
            'input_usage' => ['view', 'create'],
            'attendance' => ['view_own'],
            'payroll' => ['view_own'],
            'task' => ['view_own'],
        ],
    ];

    private array $displayNames = [
        'owner' => 'Company Owner',
        'manager' => 'Manager',
        'supervisor' => 'Supervisor',
        'finance' => 'Finance',
        'worker' => 'Worker',
    ];

    /**
     * Creates any of the 5 standard roles this company is still
     * missing (safe/idempotent — firstOrCreate), and (re)syncs each
     * one's permissions from the matrix (syncWithoutDetaching — never
     * revokes a permission a role already has, only ever adds).
     */
    public function provisionRolesForCompany(int $companyId): void
    {
        foreach ($this->matrix as $slug => $modulePermissions) {
            $role = Role::firstOrCreate(
                ['company_id' => $companyId, 'slug' => $slug],
                ['name' => $this->displayNames[$slug] ?? ucfirst($slug), 'is_system' => true]
            );

            $names = [];
            foreach ($modulePermissions as $module => $actions) {
                foreach ($actions as $action) {
                    $names[] = "{$module}.{$action}";
                }
            }

            if ($names === []) {
                continue;
            }

            $permissionIds = Permission::whereIn('name', $names)->pluck('id');
            $role->permissions()->syncWithoutDetaching($permissionIds);
        }
    }

    /** Backfill helper — every company in the system, safe to run repeatedly. */
    public function provisionAllCompanies(): void
    {
        Company::pluck('id')->each(fn ($companyId) => $this->provisionRolesForCompany($companyId));
    }
}
