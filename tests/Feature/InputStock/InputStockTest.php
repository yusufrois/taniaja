<?php

namespace Tests\Feature\InputStock;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the base InputItem/InputPurchase/InputUsage CRUD and stock
 * math. Cost-recognition-at-usage-time behavior (Expense creation,
 * weighted average, frozen cost) is covered separately in
 * InputStockCostRevisionTest — see that file for why purchases no
 * longer create an Expense directly.
 */
class InputStockTest extends TestCase
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

    public function test_owner_can_create_an_input_item(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['input_item.create']);

        $response = $this->actingAs($owner)->postJson('/api/v1/input-items', [
            'name' => 'Pupuk NPK',
            'unit' => 'kg',
            'category' => 'pupuk',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('input_items', ['name' => 'Pupuk NPK', 'company_id' => $company->id]);
    }

    public function test_buying_input_stock_increases_current_stock(): void
    {
        $company = Company::factory()->create();
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $owner = $this->makeUserWithRole($company, 'owner', ['input_item.create', 'input_item.view', 'input_purchase.create']);

        $item = $this->actingAs($owner)->postJson('/api/v1/input-items', ['name' => 'Pupuk NPK', 'unit' => 'kg']);
        $itemId = $item->json('data.id');

        $purchase = $this->actingAs($owner)->postJson('/api/v1/input-purchases', [
            'input_item_id' => $itemId,
            'supplier_id' => $supplier->id,
            'purchase_date' => now()->toDateString(),
            'quantity' => 50,
            'unit_price' => 20000,
        ]);

        $purchase->assertCreated();

        $show = $this->actingAs($owner)->getJson("/api/v1/input-items/{$itemId}");
        $this->assertEquals(50.0, $show->json('data.current_stock'));
    }

    public function test_field_worker_can_record_usage_which_reduces_stock(): void
    {
        $company = Company::factory()->create();
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $owner = $this->makeUserWithRole($company, 'owner', ['input_item.create', 'input_purchase.create']);
        $worker = $this->makeUserWithRole($company, 'worker', ['input_usage.create', 'input_item.view']);

        $item = $this->actingAs($owner)->postJson('/api/v1/input-items', ['name' => 'Pupuk NPK', 'unit' => 'kg']);
        $itemId = $item->json('data.id');

        $this->actingAs($owner)->postJson('/api/v1/input-purchases', [
            'input_item_id' => $itemId, 'supplier_id' => $supplier->id,
            'purchase_date' => now()->toDateString(), 'quantity' => 50, 'unit_price' => 20000,
        ])->assertCreated();

        $usage = $this->actingAs($worker)->postJson('/api/v1/input-usages', [
            'input_item_id' => $itemId,
            'used_date' => now()->toDateString(),
            'quantity' => 15,
        ]);
        $usage->assertCreated();

        $show = $this->actingAs($worker)->getJson("/api/v1/input-items/{$itemId}");
        $this->assertEquals(35.0, $show->json('data.current_stock')); // 50 - 15
    }

    public function test_cannot_use_more_input_stock_than_available(): void
    {
        $company = Company::factory()->create();
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $owner = $this->makeUserWithRole($company, 'owner', ['input_item.create', 'input_purchase.create', 'input_usage.create']);

        $item = $this->actingAs($owner)->postJson('/api/v1/input-items', ['name' => 'Pupuk NPK', 'unit' => 'kg']);
        $itemId = $item->json('data.id');

        $this->actingAs($owner)->postJson('/api/v1/input-purchases', [
            'input_item_id' => $itemId, 'supplier_id' => $supplier->id,
            'purchase_date' => now()->toDateString(), 'quantity' => 20, 'unit_price' => 20000,
        ])->assertCreated();

        $overuse = $this->actingAs($owner)->postJson('/api/v1/input-usages', [
            'input_item_id' => $itemId,
            'used_date' => now()->toDateString(),
            'quantity' => 25, // more than the 20kg bought
        ]);

        $overuse->assertStatus(422);
        $overuse->assertJsonValidationErrors('quantity');
    }

    /**
     * Matches the business scheme exactly: a Supervisor/field team CAN
     * record usage but must NOT be able to initiate a purchase — that's
     * the Owner's job in the "petani + tim" scenario.
     */
    public function test_supervisor_cannot_create_an_input_purchase(): void
    {
        $company = Company::factory()->create();
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $item = \App\Models\InputItem::factory()->create(['company_id' => $company->id]);
        $supervisor = $this->makeUserWithRole($company, 'supervisor', ['input_usage.create']); // no input_purchase.create

        $response = $this->actingAs($supervisor)->postJson('/api/v1/input-purchases', [
            'input_item_id' => $item->id, 'supplier_id' => $supplier->id,
            'purchase_date' => now()->toDateString(), 'quantity' => 10, 'unit_price' => 15000,
        ]);

        $response->assertForbidden();
    }

    public function test_deleting_a_usage_restores_the_stock(): void
    {
        $company = Company::factory()->create();
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $owner = $this->makeUserWithRole($company, 'owner', [
            'input_item.create', 'input_item.view', 'input_purchase.create', 'input_usage.create', 'input_usage.delete',
        ]);

        $item = $this->actingAs($owner)->postJson('/api/v1/input-items', ['name' => 'Pupuk NPK', 'unit' => 'kg']);
        $itemId = $item->json('data.id');

        $this->actingAs($owner)->postJson('/api/v1/input-purchases', [
            'input_item_id' => $itemId, 'supplier_id' => $supplier->id,
            'purchase_date' => now()->toDateString(), 'quantity' => 50, 'unit_price' => 20000,
        ])->assertCreated();

        $usage = $this->actingAs($owner)->postJson('/api/v1/input-usages', [
            'input_item_id' => $itemId, 'used_date' => now()->toDateString(), 'quantity' => 20,
        ]);
        $usageId = $usage->json('data.id');

        $item2 = $this->actingAs($owner)->getJson("/api/v1/input-items/{$itemId}");
        $this->assertEquals(30.0, $item2->json('data.current_stock')); // 50 - 20

        $this->actingAs($owner)->deleteJson("/api/v1/input-usages/{$usageId}")->assertOk();

        $item3 = $this->actingAs($owner)->getJson("/api/v1/input-items/{$itemId}");
        $this->assertEquals(50.0, $item3->json('data.current_stock')); // restored
    }
}
