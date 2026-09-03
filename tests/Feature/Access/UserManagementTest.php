<?php

namespace Tests\Feature\Access;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithRole(Company $company, string $slug, array $permissionNames = []): User
    {
        $role = Role::create(['company_id' => $company->id, 'slug' => $slug, 'name' => ucfirst($slug)]);

        if ($permissionNames) {
            $ids = collect($permissionNames)->map(
                fn ($name) => Permission::firstOrCreate(['name' => $name], ['module' => explode('.', $name)[0]])->id
            );
            $role->permissions()->sync($ids);
        }

        $user = User::factory()->create(['company_id' => $company->id]);
        $user->roles()->attach($role->id);

        return $user;
    }

    public function test_owner_can_add_a_staff_member_with_a_role(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['user.create', 'user.view']);
        $staffRole = Role::create(['company_id' => $company->id, 'slug' => 'clerk', 'name' => 'Clerk']);

        $response = $this->actingAs($owner)->postJson('/api/v1/users', [
            'name' => 'Budi Kulakan',
            'email' => 'budi@ladangwohijo.test',
            'password' => 'password123',
            'role_id' => $staffRole->id,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('users', ['email' => 'budi@ladangwohijo.test', 'company_id' => $company->id]);

        $newUser = User::where('email', 'budi@ladangwohijo.test')->first();
        $this->assertTrue($newUser->hasRole('clerk'));
    }

    public function test_newly_created_staff_member_can_actually_log_in(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['user.create']);
        $staffRole = Role::create(['company_id' => $company->id, 'slug' => 'clerk', 'name' => 'Clerk']);

        $this->actingAs($owner)->postJson('/api/v1/users', [
            'name' => 'Budi Kulakan',
            'email' => 'budi@ladangwohijo.test',
            'password' => 'password123',
            'role_id' => $staffRole->id,
        ])->assertCreated();

        $login = $this->postJson('/api/v1/login', [
            'email' => 'budi@ladangwohijo.test',
            'password' => 'password123',
        ]);
        $login->assertOk();
        $this->assertNotEmpty($login->json('token'));
    }

    public function test_user_without_permission_cannot_create_staff(): void
    {
        $company = Company::factory()->create();
        $worker = $this->makeUserWithRole($company, 'worker', []); // no user.create
        $staffRole = Role::create(['company_id' => $company->id, 'slug' => 'clerk', 'name' => 'Clerk']);

        $response = $this->actingAs($worker)->postJson('/api/v1/users', [
            'name' => 'Budi', 'email' => 'budi2@test.test', 'password' => 'password123', 'role_id' => $staffRole->id,
        ]);
        $response->assertForbidden();
    }

    public function test_role_id_from_a_different_company_is_rejected(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $owner = $this->makeUserWithRole($companyA, 'owner', ['user.create']);
        $roleFromCompanyB = Role::create(['company_id' => $companyB->id, 'slug' => 'clerk', 'name' => 'Clerk']);

        $response = $this->actingAs($owner)->postJson('/api/v1/users', [
            'name' => 'Budi', 'email' => 'budi3@test.test', 'password' => 'password123',
            'role_id' => $roleFromCompanyB->id, // belongs to a DIFFERENT company
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('role_id');
    }

    /**
     * The most important test here: an Owner of Company A must never be
     * able to see, update, or deactivate a staff member of Company B —
     * this is NOT automatic here (User doesn't use the BelongsToCompany
     * global scope other models get), so it's exactly the kind of bug
     * that could slip through if the controller's manual company check
     * were ever accidentally removed.
     */
    public function test_owner_cannot_see_or_manage_users_from_a_different_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $ownerA = $this->makeUserWithRole($companyA, 'owner', ['user.view', 'user.update', 'user.delete']);
        $userInCompanyB = $this->makeUserWithRole($companyB, 'clerk', []);

        $show = $this->actingAs($ownerA)->getJson("/api/v1/users/{$userInCompanyB->id}");
        $show->assertNotFound();

        $update = $this->actingAs($ownerA)->putJson("/api/v1/users/{$userInCompanyB->id}", ['name' => 'Diubah Paksa']);
        $update->assertNotFound();

        $delete = $this->actingAs($ownerA)->deleteJson("/api/v1/users/{$userInCompanyB->id}");
        $delete->assertNotFound();

        // Sanity: userInCompanyB is completely untouched.
        $this->assertNotEquals('Diubah Paksa', $userInCompanyB->fresh()->name);
        $this->assertEquals('active', $userInCompanyB->fresh()->status);
    }

    public function test_owner_lists_only_their_own_companys_users(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $ownerA = $this->makeUserWithRole($companyA, 'owner', ['user.view']);
        $this->makeUserWithRole($companyA, 'clerk_a', []);
        $this->makeUserWithRole($companyB, 'clerk_b', []); // different company

        $list = $this->actingAs($ownerA)->getJson('/api/v1/users');
        $list->assertOk();

        // ownerA themselves + clerk_a = 2, NOT clerk_b from company B.
        $this->assertCount(2, $list->json('data'));
    }

    public function test_deactivating_a_user_blocks_login(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['user.delete', 'user.view']);
        $staff = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'staff@test.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $this->actingAs($owner)->deleteJson("/api/v1/users/{$staff->id}")->assertOk();

        $login = $this->postJson('/api/v1/login', [
            'email' => 'staff@test.test',
            'password' => 'password123',
        ]);
        $login->assertStatus(403);
    }

    public function test_owner_can_update_a_staff_members_role_and_supervisor(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['user.update', 'user.view']);
        $roleA = Role::create(['company_id' => $company->id, 'slug' => 'clerk_a', 'name' => 'Clerk A']);
        $roleB = Role::create(['company_id' => $company->id, 'slug' => 'clerk_b', 'name' => 'Clerk B']);

        $staff = User::factory()->create(['company_id' => $company->id]);
        $staff->roles()->attach($roleA->id);

        $supervisor = User::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($owner)->putJson("/api/v1/users/{$staff->id}", [
            'role_id' => $roleB->id,
            'supervisor_id' => $supervisor->id,
        ]);
        $response->assertOk();

        $staff->refresh();
        $this->assertTrue($staff->hasRole('clerk_b'));
        $this->assertFalse($staff->hasRole('clerk_a')); // replaced, not accumulated
        $this->assertEquals($supervisor->id, $staff->supervisor_id);
    }
}
