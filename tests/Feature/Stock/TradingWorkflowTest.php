<?php

namespace Tests\Feature\Stock;

use App\Models\Company;
use App\Models\Crop;
use App\Models\ExpenseCategory;
use App\Models\Grade;
use App\Models\Greenhouse;
use App\Models\Harvest;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Season;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Variety;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TradingWorkflowTest extends TestCase
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

    /**
     * The core "tengkulak" scenario: buy from a supplier, add a landed
     * cost (transport), then sell — profit must equal
     * revenue - (purchase + transport), not just revenue - purchase.
     */
    public function test_buy_then_sell_computes_profit_including_landed_cost(): void
    {
        $company = Company::factory()->create();
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $crop = Crop::factory()->create(['company_id' => $company->id]);
        $category = ExpenseCategory::factory()->create(['company_id' => $company->id]);
        $finance = $this->makeUserWithRole($company, 'finance', [
            'purchase.create', 'purchase.view', 'expense.create', 'sale.create', 'sale.view', 'cost.view',
        ]);

        // Buy 200kg at Rp8.000/kg = Rp1.600.000
        $purchase = $this->actingAs($finance)->postJson('/api/v1/purchases', [
            'supplier_id' => $supplier->id,
            'crop_id' => $crop->id,
            'purchase_date' => now()->toDateString(),
            'quantity' => 200,
            'unit_price' => 8000,
        ]);
        $purchase->assertCreated();
        $purchaseId = $purchase->json('data.id');

        // Landed cost: Rp200.000 transport, linked to this purchase
        $this->actingAs($finance)->postJson('/api/v1/expenses', [
            'purchase_id' => $purchaseId,
            'expense_category_id' => $category->id,
            'date' => now()->toDateString(),
            'amount' => 200000,
            'description' => 'Ongkos transport',
        ])->assertCreated();

        // Total landed cost = 1.600.000 + 200.000 = 1.800.000 / 200kg = Rp9.000/kg
        $batches = $this->actingAs($finance)->getJson('/api/v1/stock-batches?source_type=purchased');
        $batches->assertOk();
        $batchId = $batches->json('data.0.id');
        $this->assertEquals(9000, $batches->json('data.0.unit_cost'));

        // Sell all 200kg at Rp12.000/kg = Rp2.400.000 revenue
        $sale = $this->actingAs($finance)->postJson("/api/v1/stock-batches/{$batchId}/sell", [
            'quantity_sold' => 200,
            'sale_price_per_unit' => 12000,
        ]);
        $sale->assertCreated();

        // Profit = 2.400.000 - (200 * 9.000) = 2.400.000 - 1.800.000 = 600.000
        // Decimal-cast columns serialize to JSON as strings with 2 decimals
        // (e.g. "2400000.00"), not as bare numbers — compare accordingly.
        $sale->assertJsonPath('data.revenue', '2400000.00');
        $sale->assertJsonPath('data.cost', '1800000.00');
        $sale->assertJsonPath('data.profit', '600000.00');
    }

    public function test_cannot_sell_more_than_available_quantity(): void
    {
        $company = Company::factory()->create();
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $crop = Crop::factory()->create(['company_id' => $company->id]);
        $finance = $this->makeUserWithRole($company, 'finance', ['purchase.create', 'purchase.view', 'sale.create', 'sale.view']);

        $purchase = $this->actingAs($finance)->postJson('/api/v1/purchases', [
            'supplier_id' => $supplier->id,
            'crop_id' => $crop->id,
            'purchase_date' => now()->toDateString(),
            'quantity' => 100,
            'unit_price' => 5000,
        ]);
        $batches = $this->actingAs($finance)->getJson('/api/v1/stock-batches');
        $batchId = $batches->json('data.0.id');

        $overSell = $this->actingAs($finance)->postJson("/api/v1/stock-batches/{$batchId}/sell", [
            'quantity_sold' => 150, // more than the 100kg bought
            'sale_price_per_unit' => 7000,
        ]);
        $overSell->assertStatus(422);
    }

    public function test_own_harvest_and_purchased_stock_are_kept_separate(): void
    {
        $company = Company::factory()->create();
        $greenhouse = Greenhouse::factory()->create(['company_id' => $company->id]);
        $crop = Crop::factory()->create(['company_id' => $company->id]);
        $variety = Variety::factory()->create(['company_id' => $company->id, 'crop_id' => $crop->id]);
        $grade = Grade::factory()->create(['company_id' => $company->id]);
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);

        $season = Season::create([
            'company_id' => $company->id,
            'greenhouse_id' => $greenhouse->id,
            'crop_id' => $crop->id,
            'variety_id' => $variety->id,
            'season_name' => 'Musim Trading Test',
            'planting_date' => now()->subDays(60),
            'status' => 'active',
        ]);

        $owner = $this->makeUserWithRole($company, 'owner', [
            'harvest.create', 'harvest.view', 'purchase.create', 'purchase.view', 'sale.view',
        ]);

        // Own harvest
        $this->actingAs($owner)->postJson('/api/v1/harvests', [
            'season_id' => $season->id,
            'harvest_date' => now()->toDateString(),
            'items' => [
                ['grade_id' => $grade->id, 'weight' => 300],
            ],
        ])->assertCreated();

        // Purchased from supplier
        $this->actingAs($owner)->postJson('/api/v1/purchases', [
            'supplier_id' => $supplier->id,
            'crop_id' => $crop->id,
            'purchase_date' => now()->toDateString(),
            'quantity' => 150,
            'unit_price' => 6000,
        ])->assertCreated();

        $ownHarvestOnly = $this->actingAs($owner)->getJson('/api/v1/stock-batches?source_type=own_harvest');
        $purchasedOnly = $this->actingAs($owner)->getJson('/api/v1/stock-batches?source_type=purchased');

        $this->assertCount(1, $ownHarvestOnly->json('data'));
        $this->assertCount(1, $purchasedOnly->json('data'));
        $this->assertEquals('own_harvest', $ownHarvestOnly->json('data.0.source_type'));
        $this->assertEquals('purchased', $purchasedOnly->json('data.0.source_type'));
    }
}
