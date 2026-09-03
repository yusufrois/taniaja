<?php

namespace Tests\Feature\Tenancy;

use App\Models\Company;
use App\Models\Greenhouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_see_greenhouses_of_another_company(): void
    {
        $companyA = Company::factory()->create(['code' => 'A']);
        $companyB = Company::factory()->create(['code' => 'B']);

        $userA = User::factory()->create(['company_id' => $companyA->id]);
        $userB = User::factory()->create(['company_id' => $companyB->id]);

        Greenhouse::factory()->create(['company_id' => $companyA->id, 'code' => 'GH-A-1']);
        Greenhouse::factory()->create(['company_id' => $companyB->id, 'code' => 'GH-B-1']);

        $this->actingAs($userA);
        $this->assertCount(1, Greenhouse::all());
        $this->assertEquals('GH-A-1', Greenhouse::first()->code);

        $this->actingAs($userB);
        $this->assertCount(1, Greenhouse::all());
        $this->assertEquals('GH-B-1', Greenhouse::first()->code);
    }

    public function test_creating_record_auto_fills_company_id_from_authenticated_user(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user);

        $greenhouse = Greenhouse::create(['code' => 'GH-X', 'name' => 'Test GH']);

        $this->assertEquals($company->id, $greenhouse->company_id);
    }

    public function test_super_admin_can_see_all_companies_data(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();

        Greenhouse::factory()->create(['company_id' => $companyA->id]);
        Greenhouse::factory()->create(['company_id' => $companyB->id]);

        $superAdmin = User::factory()->create(['company_id' => null]);
        $role = \App\Models\Role::firstOrCreate(
            ['company_id' => null, 'slug' => 'super-admin'],
            ['name' => 'Super Admin', 'is_system' => true]
        );
        $superAdmin->roles()->attach($role->id);

        $this->actingAs($superAdmin);
        $this->assertCount(2, Greenhouse::all());
    }
}
