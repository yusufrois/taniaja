<?php

namespace Tests\Feature\Sales;

use App\Models\Company;
use App\Models\Crop;
use App\Models\Customer;
use App\Models\ExpenseCategory;
use App\Models\Grade;
use App\Models\Greenhouse;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Season;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Variety;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesWorkflowTest extends TestCase
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

    public function test_invoice_number_is_auto_generated_and_unique_per_company(): void
    {
        $company = Company::factory()->create(['code' => 'LWI']);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $finance = $this->makeUserWithRole($company, 'finance', ['sale.create', 'sale.view']);

        $first = $this->actingAs($finance)->postJson('/api/v1/sales', [
            'customer_id' => $customer->id,
            'date' => now()->toDateString(),
            'items' => [
                ['description' => 'Melon Grade A', 'quantity' => 10, 'price' => 15000],
            ],
        ]);
        $second = $this->actingAs($finance)->postJson('/api/v1/sales', [
            'customer_id' => $customer->id,
            'date' => now()->toDateString(),
            'items' => [
                ['description' => 'Melon Grade B', 'quantity' => 5, 'price' => 12000],
            ],
        ]);

        $first->assertCreated();
        $second->assertCreated();

        $firstInvoice = $first->json('data.invoice_number');
        $secondInvoice = $second->json('data.invoice_number');

        $this->assertNotEquals($firstInvoice, $secondInvoice);
        $this->assertStringStartsWith('INV/LWI/', $firstInvoice);
    }

    /**
     * The full loop: harvest own greenhouse produce -> becomes a
     * StockBatch -> sold via a proper invoiced Sale -> profit tracked
     * per line item, and the season dashboard's total_sales reflects it.
     */
    public function test_selling_own_harvest_via_invoice_tracks_profit_and_season_sales(): void
    {
        $company = Company::factory()->create();
        $greenhouse = Greenhouse::factory()->create(['company_id' => $company->id]);
        $crop = Crop::factory()->create(['company_id' => $company->id]);
        $variety = Variety::factory()->create(['company_id' => $company->id, 'crop_id' => $crop->id]);
        $grade = Grade::factory()->create(['company_id' => $company->id]);
        $category = ExpenseCategory::factory()->create(['company_id' => $company->id]);
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $season = Season::create([
            'company_id' => $company->id,
            'greenhouse_id' => $greenhouse->id,
            'crop_id' => $crop->id,
            'variety_id' => $variety->id,
            'season_name' => 'Musim Jual',
            'planting_date' => now()->subDays(60),
            'status' => 'active',
        ]);

        $owner = $this->makeUserWithRole($company, 'owner', [
            'expense.create', 'harvest.create', 'harvest.view', 'sale.create', 'sale.view', 'season.view', 'cost.view',
        ]);

        // Season cost so far: Rp2.000.000
        $this->actingAs($owner)->postJson('/api/v1/expenses', [
            'season_id' => $season->id,
            'expense_category_id' => $category->id,
            'date' => now()->toDateString(),
            'amount' => 2000000,
        ])->assertCreated();

        // Harvest 100kg -> unit_cost = 2.000.000 / 100 = Rp20.000/kg
        $harvest = $this->actingAs($owner)->postJson('/api/v1/harvests', [
            'season_id' => $season->id,
            'harvest_date' => now()->toDateString(),
            'items' => [
                ['grade_id' => $grade->id, 'weight' => 100],
            ],
        ]);
        $harvest->assertCreated();

        $batches = $this->actingAs($owner)->getJson('/api/v1/stock-batches?source_type=own_harvest');
        $batchId = $batches->json('data.0.id');
        $this->assertEquals('20000.00', $batches->json('data.0.unit_cost'));

        // Sell all 100kg at Rp30.000/kg via a proper invoice
        $sale = $this->actingAs($owner)->postJson('/api/v1/sales', [
            'customer_id' => $customer->id,
            'season_id' => $season->id,
            'date' => now()->toDateString(),
            'items' => [
                [
                    'stock_batch_id' => $batchId,
                    'description' => 'Melon Grade A',
                    'quantity' => 100,
                    'unit' => 'kg',
                    'price' => 30000,
                ],
            ],
        ]);
        $sale->assertCreated();

        // Revenue 3.000.000, cost 2.000.000, profit 1.000.000.
        // 'total' and 'items.0.profit' are real decimal DB columns
        // (serialize as "X.00" strings) — assertJsonPath's strict ===
        // is fine for those. 'total_profit' is a computed Attribute
        // that serializes as a bare JSON number; assertJsonPath's ===
        // would fail on int-vs-float (1000000 !== 1000000.0), so this
        // one is fetched and compared with assertEquals (==) instead.
        $sale->assertJsonPath('data.total', '3000000.00');
        $this->assertEquals(1000000, $sale->json('data.total_profit'));
        $sale->assertJsonPath('data.items.0.profit', '1000000.00');

        // Batch should now be depleted
        $depletedCheck = $this->actingAs($owner)->getJson('/api/v1/stock-batches?source_type=own_harvest');
        $this->assertCount(0, $depletedCheck->json('data')); // active-only filter excludes depleted batches

        // Season dashboard reflects both harvest weight and sales total —
        // same int-vs-float risk as total_profit above, same fix.
        $dashboard = $this->actingAs($owner)->getJson("/api/v1/seasons/{$season->id}/dashboard");
        $dashboard->assertOk();
        $this->assertEquals(100, $dashboard->json('summary.total_harvest_kg'));
        $this->assertEquals(3000000, $dashboard->json('summary.total_sales'));
    }

    public function test_selling_more_than_available_stock_is_rejected(): void
    {
        $company = Company::factory()->create();
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $crop = Crop::factory()->create(['company_id' => $company->id]);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $finance = $this->makeUserWithRole($company, 'finance', ['purchase.create', 'purchase.view', 'sale.create', 'sale.view']);

        $this->actingAs($finance)->postJson('/api/v1/purchases', [
            'supplier_id' => $supplier->id,
            'crop_id' => $crop->id,
            'purchase_date' => now()->toDateString(),
            'quantity' => 50,
            'unit_price' => 5000,
        ])->assertCreated();

        $batches = $this->actingAs($finance)->getJson('/api/v1/stock-batches?source_type=purchased');
        $batchId = $batches->json('data.0.id');

        $oversell = $this->actingAs($finance)->postJson('/api/v1/sales', [
            'customer_id' => $customer->id,
            'date' => now()->toDateString(),
            'items' => [
                ['stock_batch_id' => $batchId, 'description' => 'Melon', 'quantity' => 999, 'price' => 8000],
            ],
        ]);

        $oversell->assertStatus(422);
    }

    public function test_sale_payment_cannot_exceed_remaining_balance(): void
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $finance = $this->makeUserWithRole($company, 'finance', ['sale.create', 'sale.update', 'sale.view']);

        $sale = $this->actingAs($finance)->postJson('/api/v1/sales', [
            'customer_id' => $customer->id,
            'date' => now()->toDateString(),
            'items' => [
                ['description' => 'Melon', 'quantity' => 10, 'price' => 10000],
            ],
        ]);
        $saleId = $sale->json('data.id'); // total = 100.000

        $overpay = $this->actingAs($finance)->postJson("/api/v1/sales/{$saleId}/payments", [
            'payment_date' => now()->toDateString(),
            'amount' => 500000,
        ]);
        $overpay->assertStatus(422);
    }
}
