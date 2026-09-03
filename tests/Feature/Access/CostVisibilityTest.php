<?php

namespace Tests\Feature\Access;

use App\Models\Company;
use App\Models\Crop;
use App\Models\Customer;
use App\Models\Grade;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CostVisibilityTest extends TestCase
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

    public function test_user_without_cost_view_does_not_see_purchase_price_fields(): void
    {
        $company = Company::factory()->create();
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $crop = Crop::factory()->create(['company_id' => $company->id]);
        $warehouseStaff = $this->makeUserWithRole($company, 'warehouse', ['purchase.view', 'purchase.create']); // no cost.view

        $create = $this->actingAs($warehouseStaff)->postJson('/api/v1/purchases', [
            'supplier_id' => $supplier->id, 'crop_id' => $crop->id,
            'purchase_date' => now()->toDateString(), 'quantity' => 100, 'unit_price' => 5000,
        ]);
        $create->assertCreated();

        // Price/cost fields must be entirely ABSENT, not null.
        $this->assertArrayNotHasKey('unit_price', $create->json('data'));
        $this->assertArrayNotHasKey('total_amount', $create->json('data'));
        $this->assertArrayNotHasKey('total_paid', $create->json('data'));
        $this->assertArrayNotHasKey('remaining', $create->json('data'));
        $this->assertArrayNotHasKey('payment_status', $create->json('data'));

        // Non-price fields (quantity, supplier, etc.) remain visible.
        $this->assertEquals('100.00', $create->json('data.quantity'));
    }

    public function test_user_with_cost_view_sees_purchase_price_fields(): void
    {
        $company = Company::factory()->create();
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $crop = Crop::factory()->create(['company_id' => $company->id]);
        $finance = $this->makeUserWithRole($company, 'finance', ['purchase.view', 'purchase.create', 'cost.view']);

        $create = $this->actingAs($finance)->postJson('/api/v1/purchases', [
            'supplier_id' => $supplier->id, 'crop_id' => $crop->id,
            'purchase_date' => now()->toDateString(), 'quantity' => 100, 'unit_price' => 5000,
        ]);
        $create->assertCreated();

        $this->assertEquals('5000.00', $create->json('data.unit_price'));
        $this->assertEquals('500000.00', $create->json('data.total_amount'));
    }

    /**
     * The exact scenario the person described: User B (gudang) uses
     * the predetermined SALE price but must never see purchase cost or
     * profit margin — sale price/subtotal/total stay visible, cost and
     * profit are hidden.
     */
    public function test_warehouse_user_sees_sale_price_but_not_cost_or_profit(): void
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $warehouseStaff = $this->makeUserWithRole($company, 'warehouse', ['sale.view', 'sale.create']); // no cost.view

        $sale = $this->actingAs($warehouseStaff)->postJson('/api/v1/sales', [
            'customer_id' => $customer->id,
            'date' => now()->toDateString(),
            'items' => [
                ['description' => 'Melon Grade A', 'quantity' => 10, 'price' => 30000],
            ],
        ]);
        $sale->assertCreated();

        // Sale price / total ARE visible — User B needs these to make a Surat Jalan/invoice.
        $this->assertEquals('300000.00', $sale->json('data.total'));
        $this->assertEquals('30000.00', $sale->json('data.items.0.price'));
        $this->assertEquals('300000.00', $sale->json('data.items.0.subtotal'));

        // But cost/profit are NOT visible anywhere in the response.
        $this->assertArrayNotHasKey('total_profit', $sale->json('data'));
        $this->assertArrayNotHasKey('cost', $sale->json('data.items.0'));
        $this->assertArrayNotHasKey('profit', $sale->json('data.items.0'));
    }

    public function test_owner_with_cost_view_sees_sale_profit(): void
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $owner = $this->makeUserWithRole($company, 'owner', ['sale.view', 'sale.create', 'cost.view']);

        $sale = $this->actingAs($owner)->postJson('/api/v1/sales', [
            'customer_id' => $customer->id,
            'date' => now()->toDateString(),
            'items' => [
                ['description' => 'Melon Grade A', 'quantity' => 10, 'price' => 30000],
            ],
        ]);
        $sale->assertCreated();

        $this->assertArrayHasKey('total_profit', $sale->json('data'));
        $this->assertArrayHasKey('profit', $sale->json('data.items.0'));
    }

    public function test_user_without_cost_view_does_not_see_stock_batch_unit_cost(): void
    {
        $company = Company::factory()->create();
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $crop = Crop::factory()->create(['company_id' => $company->id]);
        $warehouseStaff = $this->makeUserWithRole($company, 'warehouse', ['purchase.create', 'sale.view']);

        $this->actingAs($warehouseStaff)->postJson('/api/v1/purchases', [
            'supplier_id' => $supplier->id, 'crop_id' => $crop->id,
            'purchase_date' => now()->toDateString(), 'quantity' => 50, 'unit_price' => 4000,
        ])->assertCreated();

        $batches = $this->actingAs($warehouseStaff)->getJson('/api/v1/stock-batches?source_type=purchased');
        $batches->assertOk();

        $this->assertArrayNotHasKey('unit_cost', $batches->json('data.0'));
        // Non-cost fields remain visible.
        $this->assertEquals('50.00', $batches->json('data.0.quantity_available'));
    }
}
