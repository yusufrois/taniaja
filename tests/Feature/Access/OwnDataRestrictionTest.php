<?php

namespace Tests\Feature\Access;

use App\Models\Company;
use App\Models\Customer;
use App\Models\ExpenseCategory;
use App\Models\Crop;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnDataRestrictionTest extends TestCase
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

    // ---------------------------------------------------------------
    // Purchase — full scenario (list scoping, full-view sees all,
    // single-record 403 for someone else's record)
    // ---------------------------------------------------------------

    public function test_view_own_only_user_sees_only_their_own_purchases_in_list(): void
    {
        $company = Company::factory()->create();
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $crop = Crop::factory()->create(['company_id' => $company->id]);

        $userA = $this->makeUserWithRole($company, 'sourcing_a', ['purchase.create', 'purchase.view_own']);
        $userB = $this->makeUserWithRole($company, 'sourcing_b', ['purchase.create', 'purchase.view_own']);

        $this->actingAs($userA)->postJson('/api/v1/purchases', [
            'supplier_id' => $supplier->id,
            'crop_id' => $crop->id,
            'purchase_date' => now()->toDateString(),
            'quantity' => 100,
            'unit_price' => 5000,
        ])->assertCreated();

        $this->actingAs($userB)->postJson('/api/v1/purchases', [
            'supplier_id' => $supplier->id,
            'crop_id' => $crop->id,
            'purchase_date' => now()->toDateString(),
            'quantity' => 50,
            'unit_price' => 6000,
        ])->assertCreated();

        $listForA = $this->actingAs($userA)->getJson('/api/v1/purchases');
        $listForA->assertOk();
        $this->assertCount(1, $listForA->json('data'));

        // Confirm the one record A sees is genuinely their own (quantity 100),
        // not B's (quantity 50).
        $this->assertEquals('100.00', $listForA->json('data.0.quantity'));
    }

    public function test_full_view_permission_sees_every_purchase_regardless_of_creator(): void
    {
        $company = Company::factory()->create();
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $crop = Crop::factory()->create(['company_id' => $company->id]);

        $userA = $this->makeUserWithRole($company, 'sourcing_a', ['purchase.create', 'purchase.view_own']);
        $userB = $this->makeUserWithRole($company, 'sourcing_b', ['purchase.create', 'purchase.view_own']);
        $manager = $this->makeUserWithRole($company, 'manager', ['purchase.view']); // full view, no view_own needed

        $this->actingAs($userA)->postJson('/api/v1/purchases', [
            'supplier_id' => $supplier->id, 'crop_id' => $crop->id,
            'purchase_date' => now()->toDateString(), 'quantity' => 100, 'unit_price' => 5000,
        ])->assertCreated();

        $this->actingAs($userB)->postJson('/api/v1/purchases', [
            'supplier_id' => $supplier->id, 'crop_id' => $crop->id,
            'purchase_date' => now()->toDateString(), 'quantity' => 50, 'unit_price' => 6000,
        ])->assertCreated();

        $listForManager = $this->actingAs($manager)->getJson('/api/v1/purchases');
        $listForManager->assertOk();
        $this->assertCount(2, $listForManager->json('data'));
    }

    public function test_view_own_only_user_gets_403_viewing_someone_elses_purchase_directly(): void
    {
        $company = Company::factory()->create();
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $crop = Crop::factory()->create(['company_id' => $company->id]);

        $userA = $this->makeUserWithRole($company, 'sourcing_a', ['purchase.create', 'purchase.view_own']);
        $userB = $this->makeUserWithRole($company, 'sourcing_b', ['purchase.create', 'purchase.view_own']);

        $bPurchase = $this->actingAs($userB)->postJson('/api/v1/purchases', [
            'supplier_id' => $supplier->id, 'crop_id' => $crop->id,
            'purchase_date' => now()->toDateString(), 'quantity' => 50, 'unit_price' => 6000,
        ]);
        $bPurchaseId = $bPurchase->json('data.id');

        $response = $this->actingAs($userA)->getJson("/api/v1/purchases/{$bPurchaseId}");
        $response->assertForbidden();

        // Sanity check: A CAN view their own.
        $aPurchase = $this->actingAs($userA)->postJson('/api/v1/purchases', [
            'supplier_id' => $supplier->id, 'crop_id' => $crop->id,
            'purchase_date' => now()->toDateString(), 'quantity' => 10, 'unit_price' => 1000,
        ]);
        $ownResponse = $this->actingAs($userA)->getJson("/api/v1/purchases/{$aPurchase->json('data.id')}");
        $ownResponse->assertOk();
    }

    // ---------------------------------------------------------------
    // Sale — list-scoping only (same underlying mechanism as Purchase,
    // so a lighter test is enough to confirm it's wired correctly here too)
    // ---------------------------------------------------------------

    public function test_view_own_only_user_sees_only_their_own_sales_in_list(): void
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $userA = $this->makeUserWithRole($company, 'sales_a', ['sale.create', 'sale.view_own']);
        $userB = $this->makeUserWithRole($company, 'sales_b', ['sale.create', 'sale.view_own']);

        $this->actingAs($userA)->postJson('/api/v1/sales', [
            'customer_id' => $customer->id,
            'date' => now()->toDateString(),
            'items' => [['description' => 'Melon A', 'quantity' => 10, 'price' => 10000]],
        ])->assertCreated();

        $this->actingAs($userB)->postJson('/api/v1/sales', [
            'customer_id' => $customer->id,
            'date' => now()->toDateString(),
            'items' => [['description' => 'Melon B', 'quantity' => 5, 'price' => 12000]],
        ])->assertCreated();

        $listForA = $this->actingAs($userA)->getJson('/api/v1/sales');
        $listForA->assertOk();
        $this->assertCount(1, $listForA->json('data'));
    }

    // ---------------------------------------------------------------
    // Expense — list-scoping only, same reasoning as Sale above
    // ---------------------------------------------------------------

    public function test_view_own_only_user_sees_only_their_own_expenses_in_list(): void
    {
        $company = Company::factory()->create();
        $category = ExpenseCategory::factory()->create(['company_id' => $company->id]);

        $userA = $this->makeUserWithRole($company, 'finance_a', ['expense.create', 'expense.view_own']);
        $userB = $this->makeUserWithRole($company, 'finance_b', ['expense.create', 'expense.view_own']);

        $this->actingAs($userA)->postJson('/api/v1/expenses', [
            'expense_category_id' => $category->id,
            'date' => now()->toDateString(),
            'amount' => 100000,
        ])->assertCreated();

        $this->actingAs($userB)->postJson('/api/v1/expenses', [
            'expense_category_id' => $category->id,
            'date' => now()->toDateString(),
            'amount' => 200000,
        ])->assertCreated();

        $listForA = $this->actingAs($userA)->getJson('/api/v1/expenses');
        $listForA->assertOk();
        $this->assertCount(1, $listForA->json('data'));
    }
}
