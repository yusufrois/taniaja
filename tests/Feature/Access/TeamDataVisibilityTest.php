<?php

namespace Tests\Feature\Access;

use App\Models\Company;
use App\Models\Crop;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamDataVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithRole(Company $company, string $slug, array $permissionNames = [], ?User $supervisor = null): User
    {
        $role = Role::create(['company_id' => $company->id, 'slug' => $slug, 'name' => ucfirst($slug)]);

        if ($permissionNames) {
            $ids = collect($permissionNames)->map(
                fn ($name) => Permission::firstOrCreate(['name' => $name], ['module' => explode('.', $name)[0]])->id
            );
            $role->permissions()->sync($ids);
        }

        $user = User::factory()->create([
            'company_id' => $company->id,
            'supervisor_id' => $supervisor?->id,
        ]);
        $user->roles()->attach($role->id);

        return $user;
    }

    /**
     * The core scenario from the person's own description: a Manager
     * with view_team sees purchases made by their direct reports AND
     * themselves, but NOT purchases made by someone who reports to a
     * DIFFERENT manager (a peer team, not their own).
     */
    public function test_manager_with_view_team_sees_own_and_direct_reports_purchases_only(): void
    {
        $company = Company::factory()->create();
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $crop = Crop::factory()->create(['company_id' => $company->id]);

        $manager = $this->makeUserWithRole($company, 'manager', ['purchase.view_team']);
        $reportA = $this->makeUserWithRole($company, 'clerk_a', ['purchase.create'], supervisor: $manager);
        $reportB = $this->makeUserWithRole($company, 'clerk_b', ['purchase.create'], supervisor: $manager);

        // A clerk under a DIFFERENT manager — must stay invisible to
        // the first manager even though they're in the same company.
        $otherManager = $this->makeUserWithRole($company, 'manager2', []);
        $unrelatedClerk = $this->makeUserWithRole($company, 'clerk_c', ['purchase.create'], supervisor: $otherManager);

        foreach ([$reportA, $reportB, $unrelatedClerk] as $i => $clerk) {
            $this->actingAs($clerk)->postJson('/api/v1/purchases', [
                'supplier_id' => $supplier->id,
                'crop_id' => $crop->id,
                'purchase_date' => now()->toDateString(),
                'quantity' => 10 + $i,
                'unit_price' => 1000,
            ])->assertCreated();
        }

        $list = $this->actingAs($manager)->getJson('/api/v1/purchases');
        $list->assertOk();

        // Manager sees exactly reportA's and reportB's purchases: 2 records.
        $this->assertCount(2, $list->json('data'));
    }

    public function test_manager_also_sees_their_own_purchases_alongside_their_teams(): void
    {
        $company = Company::factory()->create();
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $crop = Crop::factory()->create(['company_id' => $company->id]);

        $manager = $this->makeUserWithRole($company, 'manager', ['purchase.view_team', 'purchase.create']);
        $report = $this->makeUserWithRole($company, 'clerk_a', ['purchase.create'], supervisor: $manager);

        $this->actingAs($manager)->postJson('/api/v1/purchases', [
            'supplier_id' => $supplier->id, 'crop_id' => $crop->id,
            'purchase_date' => now()->toDateString(), 'quantity' => 5, 'unit_price' => 1000,
        ])->assertCreated();

        $this->actingAs($report)->postJson('/api/v1/purchases', [
            'supplier_id' => $supplier->id, 'crop_id' => $crop->id,
            'purchase_date' => now()->toDateString(), 'quantity' => 8, 'unit_price' => 1000,
        ])->assertCreated();

        $list = $this->actingAs($manager)->getJson('/api/v1/purchases');
        $list->assertOk();
        $this->assertCount(2, $list->json('data')); // their own + their report's
    }

    public function test_view_team_user_gets_403_viewing_a_purchase_outside_their_team(): void
    {
        $company = Company::factory()->create();
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $crop = Crop::factory()->create(['company_id' => $company->id]);

        $manager = $this->makeUserWithRole($company, 'manager', ['purchase.view_team']);
        $unrelatedClerk = $this->makeUserWithRole($company, 'clerk_c', ['purchase.create']); // no supervisor set

        $purchase = $this->actingAs($unrelatedClerk)->postJson('/api/v1/purchases', [
            'supplier_id' => $supplier->id, 'crop_id' => $crop->id,
            'purchase_date' => now()->toDateString(), 'quantity' => 20, 'unit_price' => 1000,
        ]);

        $response = $this->actingAs($manager)->getJson("/api/v1/purchases/{$purchase->json('data.id')}");
        $response->assertForbidden();
    }

    /**
     * Precedence check: a role that (unusually) has BOTH purchase.view
     * (full) and purchase.view_team must behave as full-view — seeing
     * purchases from users who are NOT even on their team.
     */
    public function test_full_view_permission_takes_precedence_over_view_team(): void
    {
        $company = Company::factory()->create();
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $crop = Crop::factory()->create(['company_id' => $company->id]);

        $owner = $this->makeUserWithRole($company, 'owner', ['purchase.view', 'purchase.view_team']);
        $unrelatedClerk = $this->makeUserWithRole($company, 'clerk_c', ['purchase.create']); // no relation to owner

        $this->actingAs($unrelatedClerk)->postJson('/api/v1/purchases', [
            'supplier_id' => $supplier->id, 'crop_id' => $crop->id,
            'purchase_date' => now()->toDateString(), 'quantity' => 30, 'unit_price' => 1000,
        ])->assertCreated();

        $list = $this->actingAs($owner)->getJson('/api/v1/purchases');
        $list->assertOk();
        $this->assertCount(1, $list->json('data')); // sees it despite not being on the team
    }
}
