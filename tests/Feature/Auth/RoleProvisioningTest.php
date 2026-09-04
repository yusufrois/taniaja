<?php

namespace Tests\Feature\Auth;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Services\Auth\CompanyRegistrationService;
use App\Services\Auth\RoleMatrixService;
use Database\Seeders\RoleCapabilitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bug report: "untuk peran cuma ada company owner aja" — a newly
 * registered company only ever got the 'owner' Role record (with NO
 * permissions attached), never manager/supervisor/finance/worker.
 * Root cause: RoleCapabilitySeeder only attaches permissions to roles
 * that ALREADY exist, it never creates missing ones — and
 * CompanyRegistrationService never created anything but 'owner'.
 */
class RoleProvisioningTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Permission catalog rows (the actual "greenhouse.create" etc.
     * Permission records) come from a SEPARATE seeder
     * (RolePermissionSeeder) — RoleMatrixService only ATTACHES
     * permissions that already exist by name, it doesn't create the
     * catalog itself. Every test here needs that catalog seeded
     * first, same as every other test in this app that checks actual
     * permission grants (see the makeUserWithRole() helper pattern
     * used elsewhere, which does this per-permission on the fly —
     * here it's simpler to just seed the whole catalog once).
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_registering_a_new_company_creates_all_5_standard_roles(): void
    {
        $user = app(CompanyRegistrationService::class)->register([
            'company_name' => 'PT Uji Coba', 'company_code' => 'UJICOBA',
            'owner_name' => 'Pemilik', 'owner_email' => 'owner@ujicoba.test', 'owner_password' => 'password123',
        ]);

        $slugs = Role::where('company_id', $user->company_id)->pluck('slug')->sort()->values()->all();

        $this->assertEquals(['finance', 'manager', 'owner', 'supervisor', 'worker'], $slugs);
    }

    /** The actual payoff: the new Owner can immediately DO things — not just have a role in name only. */
    public function test_owner_of_a_newly_registered_company_actually_has_permissions(): void
    {
        $user = app(CompanyRegistrationService::class)->register([
            'company_name' => 'PT Uji Coba', 'company_code' => 'UJICOBA2',
            'owner_name' => 'Pemilik', 'owner_email' => 'owner2@ujicoba.test', 'owner_password' => 'password123',
        ]);

        $this->assertTrue($user->hasPermission('greenhouse.create'));
        $this->assertTrue($user->hasPermission('expense.approve'));
        $this->assertTrue($user->hasPermission('user.create'));
    }

    /**
     * Replicates the EXACT bug the user hit: a company whose Owner
     * role exists but has zero permissions, and the other 4 roles
     * don't exist at all — then proves the seeder fixes it.
     */
    public function test_seeder_backfills_a_company_stuck_with_only_a_powerless_owner_role(): void
    {
        $company = Company::factory()->create();
        Role::create(['company_id' => $company->id, 'slug' => 'owner', 'name' => 'Company Owner', 'is_system' => true]);
        // deliberately: no permissions attached, no other roles created — the exact bug state

        (new RoleCapabilitySeeder)->run();

        $slugs = Role::where('company_id', $company->id)->pluck('slug')->sort()->values()->all();
        $this->assertEquals(['finance', 'manager', 'owner', 'supervisor', 'worker'], $slugs);

        $ownerRole = Role::where('company_id', $company->id)->where('slug', 'owner')->first();
        $this->assertTrue($ownerRole->permissions()->where('name', 'greenhouse.create')->exists());
    }

    /** Safe to run repeatedly — never duplicates roles or errors on a second pass. */
    public function test_provisioning_is_idempotent(): void
    {
        $company = Company::factory()->create();
        $service = app(RoleMatrixService::class);

        $service->provisionRolesForCompany($company->id);
        $service->provisionRolesForCompany($company->id);

        $this->assertEquals(5, Role::where('company_id', $company->id)->count());
    }

    /** Worker gets the intentionally narrow permission set — provisioning doesn't accidentally over-grant. */
    public function test_worker_role_only_gets_its_own_narrow_permission_set(): void
    {
        $company = Company::factory()->create();
        app(RoleMatrixService::class)->provisionRolesForCompany($company->id);

        $worker = User::factory()->create(['company_id' => $company->id]);
        $worker->roles()->attach(Role::where('company_id', $company->id)->where('slug', 'worker')->first());

        $this->assertTrue($worker->hasPermission('harvest.create'));
        $this->assertFalse($worker->hasPermission('greenhouse.create'));
        $this->assertFalse($worker->hasPermission('expense.approve'));
    }
}
