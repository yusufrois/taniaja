<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Creates the Super Admin (system-wide, company_id null) role
     * plus the global permission catalog. Per-company Role rows
     * (owner/manager/supervisor/worker/finance) are created when each
     * Company is provisioned — see AuthController::registerCompany
     * and DemoCompanySeeder. Attaching permissions to those roles is
     * done separately by RoleCapabilitySeeder, which runs after the
     * company's roles exist.
     */
    public function run(): void
    {
        Role::firstOrCreate(
            ['company_id' => null, 'slug' => 'super-admin'],
            ['name' => 'Super Admin', 'is_system' => true]
        );

        // NOTE: 'grade' and 'expense_category' were added in Phase 2.
        // 'purchase' was added in Phase 6 (tengkulak/trading feature —
        // buying from farmers/suppliers for resale, kept as its own
        // module distinct from 'harvest' per the person's explicit
        // requirement that own-grown and purchased stock stay separate).
        // firstOrCreate() makes this safe to re-run on an existing
        // database — it will only insert the modules/actions that
        // are missing, nothing is duplicated.
        // NOTE: 'plant_loss' was added later (mortality/plant-death
        // tracking feature, inserted into the roadmap after Phase 8).
        // 'input_item', 'input_purchase', 'input_usage' added for RBAC
        // roadmap Fase C (stok pupuk/input pertanian). 'employee' and
        // 'attendance' added for roadmap tambahan Fase G (absensi).
        $modules = [
            'company', 'user', 'greenhouse', 'crop', 'variety', 'supplier',
            'customer', 'grade', 'expense_category', 'season', 'activity',
            'template', 'capital', 'asset', 'expense', 'debt', 'harvest',
            'purchase', 'sale', 'report', 'audit', 'plant_loss',
            'input_item', 'input_purchase', 'input_usage',
            'employee', 'attendance',
            'work_type', 'piece_work_log', 'employee_loan', 'payroll',
            'task', 'delivery_note', 'accounting', 'asset_category',
        ];
        $actions = ['view', 'create', 'update', 'delete', 'approve'];

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => "{$module}.{$action}",
                ], [
                    'module' => $module,
                ]);
            }
        }

        // "view_own" — RBAC roadmap Fase A ("data milik sendiri"). A
        // lesser alternative to "{module}.view": lets a user see only
        // the records THEY created (Purchase/Sale/Expense.created_by),
        // never everyone's. Deliberately NOT added to every module via
        // the main loop above — only these 3 are relevant to the
        // business scenarios that motivated it (a sourcing/purchasing
        // clerk who should only see their own purchases, etc.), so the
        // other ~18 modules aren't cluttered with an unused permission.
        foreach (['purchase', 'sale', 'expense'] as $module) {
            Permission::firstOrCreate([
                'name' => "{$module}.view_own",
            ], [
                'module' => $module,
            ]);
        }

        // "view_team" — RBAC roadmap Fase A2 ("Manager bisa lihat data
        // semua timnya", "SPV bisa lihat bawahannya"). Same 3 modules
        // as view_own, sitting one tier above it in precedence.
        foreach (['purchase', 'sale', 'expense'] as $module) {
            Permission::firstOrCreate([
                'name' => "{$module}.view_team",
            ], [
                'module' => $module,
            ]);
        }

        // "cost.view" — RBAC roadmap Fase B: a single cross-cutting
        // permission (not per-module like view_own/view_team above)
        // that gates whether cost/margin figures are shown AT ALL —
        // Purchase.unit_price/total_amount/payment fields,
        // StockBatch.unit_cost, SaleItem.cost/profit, Sale.total_profit.
        // One permission covers all of them because they're the same
        // underlying concern ("can this person see what things cost /
        // what margin we make"), not five separate module-level toggles.
        // Enforced in the Resource classes, not a Policy — this is about
        // which FIELDS are visible on an already-authorized record, not
        // whether the record itself can be viewed at all.
        Permission::firstOrCreate([
            'name' => 'cost.view',
        ], [
            'module' => 'cost',
        ]);

        // "user.warn" — RBAC roadmap Fase A4 (Owner's authority to issue
        // warnings before deciding to suspend someone). Kept SEPARATE
        // from 'user.update' so a Manager could, in principle, be given
        // the ability to warn their own team without full staff-
        // management rights (creating accounts, changing roles).
        Permission::firstOrCreate([
            'name' => 'user.warn',
        ], [
            'module' => 'user',
        ]);

        // "attendance.view_own" — roadmap tambahan Fase G. Same tier
        // pattern as purchase/sale/expense's view_own (RBAC Fase A),
        // but note: "own" here means "this record is ABOUT me"
        // (employee_id), not "created_by" — see AttendancePolicy for
        // why this couldn't just reuse the generic base class logic.
        Permission::firstOrCreate([
            'name' => 'attendance.view_own',
        ], [
            'module' => 'attendance',
        ]);

        // "payroll.view_own" — roadmap tambahan Fase H, same reasoning
        // as attendance.view_own (see PayslipPolicy).
        Permission::firstOrCreate([
            'name' => 'payroll.view_own',
        ], [
            'module' => 'payroll',
        ]);

        // "task.view_own" — roadmap tambahan Fase I, same reasoning:
        // "own" means assigned_to OR assigned_by me, not created_by
        // (see TaskPolicy).
        Permission::firstOrCreate([
            'name' => 'task.view_own',
        ], [
            'module' => 'task',
        ]);
    }
}
