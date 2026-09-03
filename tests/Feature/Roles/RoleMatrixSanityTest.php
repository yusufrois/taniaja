<?php

namespace Tests\Feature\Roles;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleCapabilitySeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Roadmap Fase F — "rangkai jadi role siap pakai". This is NOT
 * re-testing every feature (each module already has its own tests
 * scattered across ~30 other test files built throughout this whole
 * project) — it's a SANITY CHECK that the 5 default roles, as a
 * BUNDLE, form coherent personas: an Owner can do everything, a
 * Worker is properly restricted, sensitive modules (accounting, user
 * management, audit) stay with the right people, etc.
 */
class RoleMatrixSanityTest extends TestCase
{
    use RefreshDatabase;

    private function seedFullRoleSystem(Company $company): void
    {
        foreach (['owner', 'manager', 'supervisor', 'finance', 'worker'] as $slug) {
            Role::create(['company_id' => $company->id, 'slug' => $slug, 'name' => ucfirst($slug)]);
        }

        app(RolePermissionSeeder::class)->run();
        app(RoleCapabilitySeeder::class)->run();
    }

    public function test_owner_has_the_widest_access_of_all_roles(): void
    {
        $company = Company::factory()->create();
        $this->seedFullRoleSystem($company);

        $owner = User::factory()->create(['company_id' => $company->id]);
        $owner->roles()->attach(Role::where('company_id', $company->id)->where('slug', 'owner')->first()->id);

        // Owner-exclusive/sensitive modules — nobody else should touch these.
        $this->assertTrue($owner->hasPermission('user.create')); // manage staff
        $this->assertTrue($owner->hasPermission('audit.view')); // audit trail
        $this->assertTrue($owner->hasPermission('company.update')); // company profile
        $this->assertTrue($owner->hasPermission('accounting.delete')); // full accounting control

        // Owner should also have every OTHER role's core capability.
        $this->assertTrue($owner->hasPermission('sale.create'));
        $this->assertTrue($owner->hasPermission('payroll.create'));
        $this->assertTrue($owner->hasPermission('attendance.create'));
    }

    public function test_worker_is_properly_restricted_to_field_operations(): void
    {
        $company = Company::factory()->create();
        $this->seedFullRoleSystem($company);

        $worker = User::factory()->create(['company_id' => $company->id]);
        $worker->roles()->attach(Role::where('company_id', $company->id)->where('slug', 'worker')->first()->id);

        // Worker CAN do their field job.
        $this->assertTrue($worker->hasPermission('harvest.create'));
        $this->assertTrue($worker->hasPermission('plant_loss.create'));
        $this->assertTrue($worker->hasPermission('attendance.view_own'));

        // Worker must NEVER touch sensitive/admin modules.
        $this->assertFalse($worker->hasPermission('user.create'));
        $this->assertFalse($worker->hasPermission('audit.view'));
        $this->assertFalse($worker->hasPermission('company.update'));
        $this->assertFalse($worker->hasPermission('accounting.view'));
        $this->assertFalse($worker->hasPermission('cost.view')); // never sees prices/margins
        $this->assertFalse($worker->hasPermission('user.delete'));
    }

    public function test_finance_owns_money_modules_but_not_field_operations(): void
    {
        $company = Company::factory()->create();
        $this->seedFullRoleSystem($company);

        $finance = User::factory()->create(['company_id' => $company->id]);
        $finance->roles()->attach(Role::where('company_id', $company->id)->where('slug', 'finance')->first()->id);

        // Finance runs the books.
        $this->assertTrue($finance->hasPermission('accounting.create'));
        $this->assertTrue($finance->hasPermission('payroll.create'));
        $this->assertTrue($finance->hasPermission('cost.view'));
        $this->assertTrue($finance->hasPermission('debt.create'));

        // Finance does NOT run field operations or manage staff accounts.
        $this->assertFalse($finance->hasPermission('harvest.create'));
        $this->assertFalse($finance->hasPermission('user.create'));
        $this->assertFalse($finance->hasPermission('greenhouse.create'));
    }

    public function test_supervisor_manages_the_field_team_but_not_money(): void
    {
        $company = Company::factory()->create();
        $this->seedFullRoleSystem($company);

        $supervisor = User::factory()->create(['company_id' => $company->id]);
        $supervisor->roles()->attach(Role::where('company_id', $company->id)->where('slug', 'supervisor')->first()->id);

        // Supervisor runs the field team day-to-day.
        $this->assertTrue($supervisor->hasPermission('attendance.create'));
        $this->assertTrue($supervisor->hasPermission('task.create'));
        $this->assertTrue($supervisor->hasPermission('delivery_note.create'));

        // Supervisor does NOT touch money/accounting/staff accounts.
        $this->assertFalse($supervisor->hasPermission('cost.view'));
        $this->assertFalse($supervisor->hasPermission('accounting.view'));
        $this->assertFalse($supervisor->hasPermission('user.create'));
        $this->assertFalse($supervisor->hasPermission('payroll.create'));
    }

    /**
     * A user with NO role attached at all must be able to do
     * absolutely nothing — the permission system fails CLOSED, not
     * open, if a role is somehow missing.
     */
    public function test_user_with_no_role_can_do_nothing(): void
    {
        $company = Company::factory()->create();
        $this->seedFullRoleSystem($company);

        $noRoleUser = User::factory()->create(['company_id' => $company->id]);

        $this->assertFalse($noRoleUser->hasPermission('harvest.view'));
        $this->assertFalse($noRoleUser->hasPermission('sale.create'));
        $this->assertFalse($noRoleUser->hasPermission('accounting.view'));
    }
}
