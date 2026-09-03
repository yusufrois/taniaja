<?php

namespace Tests\Feature\Access;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function makeSuperAdmin(): User
    {
        $role = Role::create(['company_id' => null, 'slug' => 'super-admin', 'name' => 'Super Admin']);
        $user = User::factory()->create(['company_id' => null]);
        $user->roles()->attach($role->id);

        return $user;
    }

    private function makeOwner(Company $company): User
    {
        $role = Role::create(['company_id' => $company->id, 'slug' => 'owner', 'name' => 'Owner']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->roles()->attach($role->id);

        return $user;
    }

    /**
     * This is the exact bug Fase A5 fixes: before, a Super Admin's OWN
     * company_id is null, so the old check
     * `$target->company_id !== auth()->user()->company_id` was ALWAYS
     * true for any real company's user — Super Admin got 404 trying to
     * manage anyone, despite being designed to bypass every permission.
     */
    public function test_super_admin_can_suspend_a_company_owners_account(): void
    {
        $superAdmin = $this->makeSuperAdmin();
        $company = Company::factory()->create();
        $owner = $this->makeOwner($company);

        $response = $this->actingAs($superAdmin)->patchJson("/api/v1/users/{$owner->id}/suspend");

        $response->assertOk();
        $response->assertJsonPath('data.status', 'inactive');
    }

    public function test_suspended_owner_cannot_log_in(): void
    {
        $superAdmin = $this->makeSuperAdmin();
        $company = Company::factory()->create();
        $role = Role::create(['company_id' => $company->id, 'slug' => 'owner', 'name' => 'Owner']);
        $owner = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'owner-to-suspend@test.test',
            'password' => Hash::make('password123'),
        ]);
        $owner->roles()->attach($role->id);

        $this->actingAs($superAdmin)->patchJson("/api/v1/users/{$owner->id}/suspend")->assertOk();

        $login = $this->postJson('/api/v1/login', ['email' => 'owner-to-suspend@test.test', 'password' => 'password123']);
        $login->assertStatus(403);
    }

    public function test_super_admin_can_issue_a_warning_to_an_owner(): void
    {
        $superAdmin = $this->makeSuperAdmin();
        $company = Company::factory()->create();
        $owner = $this->makeOwner($company);

        $response = $this->actingAs($superAdmin)->postJson("/api/v1/users/{$owner->id}/warnings", [
            'reason' => 'Melanggar ketentuan penggunaan platform',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('user_warnings', ['user_id' => $owner->id, 'issued_by' => $superAdmin->id]);
    }

    public function test_super_admin_sees_users_across_multiple_companies_in_index(): void
    {
        $superAdmin = $this->makeSuperAdmin();
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $this->makeOwner($companyA);
        $this->makeOwner($companyB);

        $response = $this->actingAs($superAdmin)->getJson('/api/v1/users');
        $response->assertOk();

        // Both owners visible — Super Admin is NOT scoped to one company,
        // unlike an ordinary Owner (see UserManagementTest for that case).
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_super_admin_can_filter_index_by_company_id(): void
    {
        $superAdmin = $this->makeSuperAdmin();
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $ownerA = $this->makeOwner($companyA);
        $this->makeOwner($companyB);

        $response = $this->actingAs($superAdmin)->getJson("/api/v1/users?company_id={$companyA->id}");
        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($ownerA->id));
        $this->assertCount(1, $ids); // only companyA's owner, not companyB's
    }

    public function test_super_admin_can_reactivate_a_previously_suspended_owner(): void
    {
        $superAdmin = $this->makeSuperAdmin();
        $company = Company::factory()->create();
        $owner = $this->makeOwner($company);
        $owner->update(['status' => 'inactive']);

        $response = $this->actingAs($superAdmin)->patchJson("/api/v1/users/{$owner->id}/reactivate");

        $response->assertOk();
        $response->assertJsonPath('data.status', 'active');
    }

    /**
     * Sanity check: fixing Super Admin's access must NOT accidentally
     * loosen the ordinary-Owner cross-company restriction from Fase A3.
     */
    public function test_ordinary_owner_still_cannot_manage_a_different_companys_user(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $ownerA = $this->makeOwner($companyA);
        $ownerB = $this->makeOwner($companyB);

        $response = $this->actingAs($ownerA)->patchJson("/api/v1/users/{$ownerB->id}/suspend");
        $response->assertNotFound();
    }
}
