<?php

namespace Tests\Feature\MasterData;

use App\Models\Company;
use App\Models\Greenhouse;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GreenhouseCrudTest extends TestCase
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

    public function test_user_with_create_permission_can_create_greenhouse(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['greenhouse.create', 'greenhouse.view']);

        $response = $this->actingAs($owner)->postJson('/api/v1/greenhouses', [
            'code' => 'GH-A',
            'name' => 'Greenhouse A',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('greenhouses', ['code' => 'GH-A', 'company_id' => $company->id]);
    }

    public function test_user_without_create_permission_cannot_create_greenhouse(): void
    {
        $company = Company::factory()->create();
        $worker = $this->makeUserWithRole($company, 'worker', []); // no permissions at all

        $response = $this->actingAs($worker)->postJson('/api/v1/greenhouses', [
            'code' => 'GH-A',
            'name' => 'Greenhouse A',
        ]);

        $response->assertForbidden();
    }

    public function test_duplicate_greenhouse_code_within_same_company_is_rejected(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['greenhouse.create', 'greenhouse.view']);
        Greenhouse::factory()->create(['company_id' => $company->id, 'code' => 'GH-A']);

        $response = $this->actingAs($owner)->postJson('/api/v1/greenhouses', [
            'code' => 'GH-A',
            'name' => 'Duplicate',
        ]);

        $response->assertStatus(422);
    }

    public function test_same_greenhouse_code_is_allowed_across_different_companies(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        Greenhouse::factory()->create(['company_id' => $companyA->id, 'code' => 'GH-A']);

        $ownerB = $this->makeUserWithRole($companyB, 'owner', ['greenhouse.create', 'greenhouse.view']);

        $response = $this->actingAs($ownerB)->postJson('/api/v1/greenhouses', [
            'code' => 'GH-A', // same code, different company — must be allowed
            'name' => 'Greenhouse A of Company B',
        ]);

        $response->assertCreated();
    }
}
