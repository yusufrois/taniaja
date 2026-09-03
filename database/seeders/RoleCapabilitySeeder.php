<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleCapabilitySeeder extends Seeder
{
    /**
     * Attaches permissions to every company's roles per the Role &
     * Permission Matrix. Structure: [role_slug => [module => [actions]]].
     * Safe to re-run: uses syncWithoutDetaching, never revokes permissions.
     *
     * Phase 6 adds 'harvest', 'purchase', and 'sale' (used by the
     * quick-sale StockBatchSale flow now, and by the full Sales module
     * in Phase 7 — same permission, same access rules).
     */
    private array $matrix = [
        'owner' => [
            'user' => ['view', 'create', 'update', 'delete', 'warn'], // manage own company's staff — RBAC Fase A3 / A4
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
            'cost' => ['view'], // RBAC Fase B — see cost/margin figures
            'input_item' => ['view', 'create', 'update', 'delete'], // RBAC Fase C
            'input_purchase' => ['view', 'create', 'update', 'delete'],
            'input_usage' => ['view', 'create', 'update', 'delete'],
            'employee' => ['view', 'create', 'update', 'delete'], // roadmap tambahan Fase G
            'attendance' => ['view', 'create', 'update', 'delete'],
            'work_type' => ['view', 'create', 'update', 'delete'], // roadmap tambahan Fase H
            'piece_work_log' => ['view', 'create', 'update', 'delete'],
            'employee_loan' => ['view', 'create', 'update', 'delete'],
            'payroll' => ['view', 'create', 'update'], // create=generate, update=finalize
            'task' => ['view', 'create', 'update', 'delete'], // roadmap tambahan Fase I
            'delivery_note' => ['view', 'create', 'update', 'delete'], // roadmap tambahan Fase D
            'accounting' => ['view', 'create', 'update', 'delete'], // roadmap tambahan Fase L1
            'company' => ['view', 'update'], // Fase F — profil perusahaan sendiri
            'audit' => ['view'], // Fase F — jejak audit, sensitif, Owner saja
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
            'cost' => ['view'], // RBAC Fase B
            'input_item' => ['view', 'create', 'update', 'delete'], // RBAC Fase C
            'input_purchase' => ['view', 'create', 'update', 'delete'],
            'input_usage' => ['view', 'create', 'update', 'delete'],
            'employee' => ['view', 'create', 'update', 'delete'], // roadmap tambahan Fase G
            'attendance' => ['view', 'create', 'update', 'delete'],
            'work_type' => ['view', 'create', 'update', 'delete'], // roadmap tambahan Fase H
            'piece_work_log' => ['view', 'create', 'update', 'delete'],
            'employee_loan' => ['view', 'create', 'update', 'delete'],
            'payroll' => ['view', 'create', 'update'],
            'task' => ['view', 'create', 'update', 'delete'], // roadmap tambahan Fase I
            'delivery_note' => ['view', 'create', 'update', 'delete'], // roadmap tambahan Fase D
            'accounting' => ['view', 'create', 'update', 'delete'], // roadmap tambahan Fase L1
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
            'input_item' => ['view'], // RBAC Fase C — Supervisor sees stock, doesn't buy
            'input_usage' => ['view', 'create'], // can record fertilizing usage
            'employee' => ['view', 'create'], // roadmap tambahan Fase G — can register field staff w/o accounts
            'attendance' => ['view', 'create', 'update'], // marks team's attendance; full view = sees whole team
                                                            // (simplification: no separate view_team tier built for
                                                            // attendance yet — see Fase G README)
            'work_type' => ['view'], // roadmap tambahan Fase H
            'piece_work_log' => ['view', 'create'], // records field team's daily piece-work results
            'task' => ['create', 'view_own'], // roadmap tambahan Fase I — assigns tasks to their team
            'delivery_note' => ['view', 'create'], // roadmap tambahan Fase D — handles physical deliveries
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
            'cost' => ['view'], // RBAC Fase B
            'input_item' => ['view'], // RBAC Fase C
            'input_purchase' => ['view'], // sees cost for accounting purposes, doesn't initiate
            'input_usage' => ['view'],
            'employee' => ['view'], // roadmap tambahan Fase G — needed for future Payroll (Fase H)
            'attendance' => ['view'], // needed for future Payroll (Fase H)
            'work_type' => ['view'], // roadmap tambahan Fase H
            'piece_work_log' => ['view'],
            'employee_loan' => ['view', 'create', 'update', 'delete'], // Finance manages kasbon
            'payroll' => ['view', 'create', 'update'], // Finance runs payroll
            'delivery_note' => ['view'], // roadmap tambahan Fase D — reconciliation
            'accounting' => ['view', 'create', 'update', 'delete'], // roadmap tambahan Fase L1 — Finance runs the books
        ],
        'worker' => [
            'activity' => ['view', 'create'],
            'harvest' => ['view', 'create'], // worker records the harvest itself
            'plant_loss' => ['view', 'create'], // worker reports plant deaths found in the field
            'input_item' => ['view'], // RBAC Fase C
            'input_usage' => ['view', 'create'], // "User A (petugas lapangan) mencatat aktivitas pemupukan"
            'attendance' => ['view_own'], // roadmap tambahan Fase G — sees only their own history;
                                           // self check-in itself needs NO permission at all (see AttendanceController)
            'payroll' => ['view_own'], // roadmap tambahan Fase H — sees only their own payslips
            'task' => ['view_own'], // roadmap tambahan Fase I — sees tasks assigned to them
        ],
    ];

    public function run(): void
    {
        $rolesBySlug = Role::whereNotNull('company_id')->get()->groupBy('slug');

        foreach ($this->matrix as $slug => $modulePermissions) {
            if (! $rolesBySlug->has($slug)) {
                continue;
            }

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

            foreach ($rolesBySlug[$slug] as $role) {
                $role->permissions()->syncWithoutDetaching($permissionIds);
            }
        }
    }
}
