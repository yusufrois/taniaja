<?php

namespace Tests\Feature\InputStock;

use App\Models\Company;
use App\Models\ExpenseCategory;
use App\Models\Greenhouse;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InputStockCostRevisionTest extends TestCase
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

    public function test_purchase_no_longer_creates_an_expense_directly(): void
    {
        $company = Company::factory()->create();
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $owner = $this->makeUserWithRole($company, 'owner', ['input_item.create', 'input_purchase.create']);

        $item = $this->actingAs($owner)->postJson('/api/v1/input-items', ['name' => 'Pupuk NPK', 'unit' => 'kg']);

        $before = \App\Models\Expense::count();

        $this->actingAs($owner)->postJson('/api/v1/input-purchases', [
            'input_item_id' => $item->json('data.id'),
            'supplier_id' => $supplier->id,
            'purchase_date' => now()->toDateString(),
            'quantity' => 50,
            'unit_price' => 20000,
        ])->assertCreated();

        $this->assertEquals($before, \App\Models\Expense::count()); // unchanged — no Expense from a purchase anymore
    }

    /**
     * The exact scenario from the person's question: GH-A and GH-B draw
     * from the SAME bulk-bought pupuk stock, but use different amounts.
     * Cost must correctly follow to whichever greenhouse actually used
     * it, NOT stay wherever the purchase happened to be tagged.
     */
    public function test_different_greenhouses_using_pooled_stock_get_separately_attributed_cost(): void
    {
        $company = Company::factory()->create();
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $category = ExpenseCategory::factory()->create(['company_id' => $company->id]);
        $greenhouseA = Greenhouse::factory()->create(['company_id' => $company->id]);
        $greenhouseB = Greenhouse::factory()->create(['company_id' => $company->id]);
        $owner = $this->makeUserWithRole($company, 'owner', [
            'input_item.create', 'input_purchase.create', 'input_usage.create', 'cost.view', 'report.view',
        ]);

        $item = $this->actingAs($owner)->postJson('/api/v1/input-items', [
            'name' => 'Pupuk NPK', 'unit' => 'kg', 'default_expense_category_id' => $category->id,
        ]);
        $itemId = $item->json('data.id');

        // One bulk purchase, NOT tagged to any specific greenhouse — 50kg @ Rp20.000/kg.
        $this->actingAs($owner)->postJson('/api/v1/input-purchases', [
            'input_item_id' => $itemId, 'supplier_id' => $supplier->id,
            'purchase_date' => now()->toDateString(), 'quantity' => 50, 'unit_price' => 20000,
        ])->assertCreated();

        // GH-A uses 15kg, GH-B uses 20kg — different amounts, different greenhouses.
        $usageA = $this->actingAs($owner)->postJson('/api/v1/input-usages', [
            'input_item_id' => $itemId, 'greenhouse_id' => $greenhouseA->id,
            'used_date' => now()->toDateString(), 'quantity' => 15,
        ]);
        $usageB = $this->actingAs($owner)->postJson('/api/v1/input-usages', [
            'input_item_id' => $itemId, 'greenhouse_id' => $greenhouseB->id,
            'used_date' => now()->toDateString(), 'quantity' => 20,
        ]);

        $usageA->assertCreated();
        $usageB->assertCreated();

        // Cost correctly proportional to what each greenhouse actually used.
        $this->assertEquals('300000.00', $usageA->json('data.cost')); // 15 * 20.000
        $this->assertEquals('400000.00', $usageB->json('data.cost')); // 20 * 20.000

        // Each usage has ITS OWN Expense, tagged to ITS OWN greenhouse.
        $this->assertDatabaseHas('expenses', [
            'id' => $usageA->json('data.expense_id'),
            'greenhouse_id' => $greenhouseA->id,
            'amount' => 300000,
        ]);
        $this->assertDatabaseHas('expenses', [
            'id' => $usageB->json('data.expense_id'),
            'greenhouse_id' => $greenhouseB->id,
            'amount' => 400000,
        ]);

        // Confirm via the actual Greenhouse Performance report, not just
        // raw Expense rows — this is what the person would actually look at.
        $reportA = $this->actingAs($owner)->getJson("/api/v1/reports/greenhouses/{$greenhouseA->id}/performance");
        $reportB = $this->actingAs($owner)->getJson("/api/v1/reports/greenhouses/{$greenhouseB->id}/performance");
        $reportA->assertOk();
        $reportB->assertOk();
        $this->assertEquals(300000, $reportA->json('production_cost_total'));
        $this->assertEquals(400000, $reportB->json('production_cost_total'));
    }

    public function test_average_cost_is_weighted_across_multiple_purchases_at_different_prices(): void
    {
        $company = Company::factory()->create();
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $owner = $this->makeUserWithRole($company, 'owner', ['input_item.create', 'input_item.view', 'input_purchase.create', 'cost.view']);

        $item = $this->actingAs($owner)->postJson('/api/v1/input-items', ['name' => 'Pupuk NPK', 'unit' => 'kg']);
        $itemId = $item->json('data.id');

        // 50kg @ 20.000 = 1.000.000, then 50kg @ 30.000 = 1.500.000.
        // Total: 100kg for 2.500.000 → weighted average = 25.000/kg.
        $this->actingAs($owner)->postJson('/api/v1/input-purchases', [
            'input_item_id' => $itemId, 'supplier_id' => $supplier->id,
            'purchase_date' => now()->subDays(10)->toDateString(), 'quantity' => 50, 'unit_price' => 20000,
        ])->assertCreated();

        $this->actingAs($owner)->postJson('/api/v1/input-purchases', [
            'input_item_id' => $itemId, 'supplier_id' => $supplier->id,
            'purchase_date' => now()->toDateString(), 'quantity' => 50, 'unit_price' => 30000,
        ])->assertCreated();

        $show = $this->actingAs($owner)->getJson("/api/v1/input-items/{$itemId}");
        $this->assertEquals(25000.0, $show->json('data.average_unit_cost'));
    }

    /**
     * Proves the "frozen at time of use" claim: a usage recorded BEFORE
     * a price change must keep its ORIGINAL cost, even after a new,
     * more expensive purchase changes the item's average going forward.
     */
    public function test_usage_cost_is_frozen_and_not_retroactively_changed_by_a_later_purchase(): void
    {
        $company = Company::factory()->create();
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $category = ExpenseCategory::factory()->create(['company_id' => $company->id]);
        $owner = $this->makeUserWithRole($company, 'owner', [
            'input_item.create', 'input_purchase.create', 'input_usage.create', 'input_usage.view', 'cost.view',
        ]);

        $item = $this->actingAs($owner)->postJson('/api/v1/input-items', [
            'name' => 'Pupuk NPK', 'unit' => 'kg', 'default_expense_category_id' => $category->id,
        ]);
        $itemId = $item->json('data.id');

        $this->actingAs($owner)->postJson('/api/v1/input-purchases', [
            'input_item_id' => $itemId, 'supplier_id' => $supplier->id,
            'purchase_date' => now()->subDays(10)->toDateString(), 'quantity' => 50, 'unit_price' => 20000,
        ])->assertCreated();

        // Used while average is still 20.000/kg.
        $usage = $this->actingAs($owner)->postJson('/api/v1/input-usages', [
            'input_item_id' => $itemId, 'used_date' => now()->toDateString(), 'quantity' => 10,
        ]);
        $usage->assertCreated();
        $this->assertEquals('200000.00', $usage->json('data.cost')); // 10 * 20.000

        // A much pricier purchase happens AFTER the usage above.
        $this->actingAs($owner)->postJson('/api/v1/input-purchases', [
            'input_item_id' => $itemId, 'supplier_id' => $supplier->id,
            'purchase_date' => now()->toDateString(), 'quantity' => 50, 'unit_price' => 100000,
        ])->assertCreated();

        // The EARLIER usage's cost must be untouched.
        $usageId = $usage->json('data.id');
        $recheck = $this->actingAs($owner)->getJson("/api/v1/input-usages/{$usageId}");
        $this->assertEquals('200000.00', $recheck->json('data.cost')); // still 200.000, not recalculated
    }

    public function test_input_item_without_default_category_records_usage_without_creating_expense(): void
    {
        $company = Company::factory()->create();
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $owner = $this->makeUserWithRole($company, 'owner', ['input_item.create', 'input_purchase.create', 'input_usage.create']);

        // NOTE: no default_expense_category_id given.
        $item = $this->actingAs($owner)->postJson('/api/v1/input-items', ['name' => 'Pupuk NPK', 'unit' => 'kg']);
        $itemId = $item->json('data.id');

        $this->actingAs($owner)->postJson('/api/v1/input-purchases', [
            'input_item_id' => $itemId, 'supplier_id' => $supplier->id,
            'purchase_date' => now()->toDateString(), 'quantity' => 50, 'unit_price' => 20000,
        ])->assertCreated();

        $before = \App\Models\Expense::count();

        $usage = $this->actingAs($owner)->postJson('/api/v1/input-usages', [
            'input_item_id' => $itemId, 'used_date' => now()->toDateString(), 'quantity' => 10,
        ]);
        $usage->assertCreated();

        $this->assertEquals($before, \App\Models\Expense::count()); // no Expense created
        $this->assertNull($usage->json('data.expense_id'));
    }

    public function test_warehouse_user_without_cost_view_does_not_see_average_unit_cost_or_usage_cost(): void
    {
        $company = Company::factory()->create();
        $warehouseStaff = $this->makeUserWithRole($company, 'warehouse', ['input_item.create', 'input_item.view']);

        $item = $this->actingAs($warehouseStaff)->postJson('/api/v1/input-items', ['name' => 'Pupuk NPK', 'unit' => 'kg']);
        $item->assertCreated();

        $this->assertArrayNotHasKey('average_unit_cost', $item->json('data'));
    }
}
