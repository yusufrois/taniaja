<?php

namespace Tests\Feature\Reports;

use App\Models\Company;
use App\Models\Crop;
use App\Models\Customer;
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

class TraceabilityReportsTest extends TestCase
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
     * The core Fase J scenario: a season's harvest gets sold via BOTH
     * paths (full invoice AND quick-sale), and the report must
     * correctly show both, attributed to the right customers.
     */
    public function test_season_traceability_shows_sales_across_both_paths(): void
    {
        $company = Company::factory()->create();
        $greenhouse = Greenhouse::factory()->create(['company_id' => $company->id]);
        $crop = Crop::factory()->create(['company_id' => $company->id]);
        $variety = Variety::factory()->create(['company_id' => $company->id, 'crop_id' => $crop->id]);
        $grade = Grade::factory()->create(['company_id' => $company->id]);
        $customerA = Customer::factory()->create(['company_id' => $company->id, 'name' => 'Toko A']);
        $customerB = Customer::factory()->create(['company_id' => $company->id, 'name' => 'Toko B']);

        $owner = $this->makeUserWithRole($company, 'owner', [
            'season.view', 'harvest.create', 'harvest.view', 'sale.create', 'sale.view', 'report.view', 'cost.view',
        ]);

        $season = Season::create([
            'company_id' => $company->id, 'greenhouse_id' => $greenhouse->id,
            'crop_id' => $crop->id, 'variety_id' => $variety->id,
            'season_name' => 'Musim Traceability', 'planting_date' => now()->subDays(60), 'status' => 'active',
        ]);

        // Harvest 100kg.
        $this->actingAs($owner)->postJson('/api/v1/harvests', [
            'season_id' => $season->id, 'harvest_date' => now()->toDateString(),
            'items' => [['grade_id' => $grade->id, 'weight' => 100]],
        ])->assertCreated();

        $batches = $this->actingAs($owner)->getJson('/api/v1/stock-batches?source_type=own_harvest');
        $batchId = $batches->json('data.0.id');

        // Sell 30kg to Toko A via full invoice.
        $this->actingAs($owner)->postJson('/api/v1/sales', [
            'customer_id' => $customerA->id, 'date' => now()->toDateString(),
            'items' => [['stock_batch_id' => $batchId, 'description' => 'Melon', 'quantity' => 30, 'price' => 15000]],
        ])->assertCreated();

        // Sell 20kg to Toko B via quick-sale.
        $this->actingAs($owner)->postJson("/api/v1/stock-batches/{$batchId}/sell", [
            'customer_id' => $customerB->id, 'sale_date' => now()->toDateString(),
            'quantity_sold' => 20, 'sale_price_per_unit' => 16000,
        ])->assertCreated();

        $report = $this->actingAs($owner)->getJson("/api/v1/reports/seasons/{$season->id}/traceability");
        $report->assertOk();

        $this->assertEquals(100.0, $report->json('total_harvested'));
        $this->assertEquals(50.0, $report->json('total_sold')); // 30 + 20
        $this->assertEquals(50.0, $report->json('total_remaining_stock')); // 100 - 50

        $soldTo = collect($report->json('stock_batches.0.sold_to'));
        // (float) cast on $s['quantity'] — PHP's json_encode() serializes
        // a whole-number float like 30.0 as bare "30" in JSON, which
        // decodes back as an int, not a float. A strict === 30.0 would
        // then wrongly fail even though the value is correct.
        $this->assertTrue($soldTo->contains(fn ($s) => $s['customer_name'] === 'Toko A' && (float) $s['quantity'] === 30.0 && $s['via'] === 'invoice'));
        $this->assertTrue($soldTo->contains(fn ($s) => $s['customer_name'] === 'Toko B' && (float) $s['quantity'] === 20.0 && $s['via'] === 'quick_sale'));
    }

    public function test_season_traceability_hides_revenue_without_cost_view(): void
    {
        $company = Company::factory()->create();
        $greenhouse = Greenhouse::factory()->create(['company_id' => $company->id]);
        $crop = Crop::factory()->create(['company_id' => $company->id]);
        $variety = Variety::factory()->create(['company_id' => $company->id, 'crop_id' => $crop->id]);
        $grade = Grade::factory()->create(['company_id' => $company->id]);
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $owner = $this->makeUserWithRole($company, 'owner', [
            'season.view', 'harvest.create', 'sale.create', 'sale.view', 'report.view', 'cost.view',
        ]);
        $warehouse = $this->makeUserWithRole($company, 'warehouse', ['report.view']); // no cost.view

        $season = Season::create([
            'company_id' => $company->id, 'greenhouse_id' => $greenhouse->id,
            'crop_id' => $crop->id, 'variety_id' => $variety->id,
            'season_name' => 'Musim B', 'planting_date' => now()->subDays(60), 'status' => 'active',
        ]);

        $this->actingAs($owner)->postJson('/api/v1/harvests', [
            'season_id' => $season->id, 'harvest_date' => now()->toDateString(),
            'items' => [['grade_id' => $grade->id, 'weight' => 50]],
        ])->assertCreated();

        $batches = $this->actingAs($owner)->getJson('/api/v1/stock-batches?source_type=own_harvest');
        $batchId = $batches->json('data.0.id');

        $this->actingAs($owner)->postJson('/api/v1/sales', [
            'customer_id' => $customer->id, 'date' => now()->toDateString(),
            'items' => [['stock_batch_id' => $batchId, 'description' => 'Melon', 'quantity' => 10, 'price' => 15000]],
        ])->assertCreated();

        $report = $this->actingAs($warehouse)->getJson("/api/v1/reports/seasons/{$season->id}/traceability");
        $report->assertOk();

        $this->assertNull($report->json('stock_batches.0.sold_to.0.revenue'));
        // Quantity (not a cost figure) stays visible.
        $this->assertEquals(10.0, $report->json('stock_batches.0.sold_to.0.quantity'));
    }

    /**
     * Fase K core scenario: purchase history per supplier, including
     * WHO on staff made the purchase.
     */
    public function test_supplier_history_lists_purchases_with_staff_who_bought(): void
    {
        $company = Company::factory()->create();
        $supplier = Supplier::factory()->create(['company_id' => $company->id, 'name' => 'Pak Tani Slamet']);
        $crop = Crop::factory()->create(['company_id' => $company->id]);

        $buyer = $this->makeUserWithRole($company, 'sourcing', ['purchase.create']);
        $owner = $this->makeUserWithRole($company, 'owner', ['report.view', 'cost.view']);

        $this->actingAs($buyer)->postJson('/api/v1/purchases', [
            'supplier_id' => $supplier->id, 'crop_id' => $crop->id,
            'purchase_date' => now()->toDateString(), 'quantity' => 100, 'unit_price' => 5000,
        ])->assertCreated();

        $report = $this->actingAs($owner)->getJson("/api/v1/reports/suppliers/{$supplier->id}/history");
        $report->assertOk();

        $this->assertEquals('Pak Tani Slamet', $report->json('supplier.name'));
        $this->assertEquals(1, $report->json('total_purchases'));
        $this->assertEquals(100.0, $report->json('total_quantity'));
        $this->assertEquals($buyer->name, $report->json('purchases.0.purchased_by'));
        $this->assertEquals(500000.0, $report->json('total_amount'));
    }

    public function test_supplier_history_hides_price_without_cost_view(): void
    {
        $company = Company::factory()->create();
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $crop = Crop::factory()->create(['company_id' => $company->id]);

        $buyer = $this->makeUserWithRole($company, 'sourcing', ['purchase.create']);
        $warehouse = $this->makeUserWithRole($company, 'warehouse', ['report.view']); // no cost.view

        $this->actingAs($buyer)->postJson('/api/v1/purchases', [
            'supplier_id' => $supplier->id, 'crop_id' => $crop->id,
            'purchase_date' => now()->toDateString(), 'quantity' => 50, 'unit_price' => 4000,
        ])->assertCreated();

        $report = $this->actingAs($warehouse)->getJson("/api/v1/reports/suppliers/{$supplier->id}/history");
        $report->assertOk();

        $this->assertNull($report->json('purchases.0.unit_price'));
        $this->assertNull($report->json('total_amount'));
        // Quantity stays visible.
        $this->assertEquals(50.0, $report->json('purchases.0.quantity'));
    }

    public function test_user_without_report_view_gets_403(): void
    {
        $company = Company::factory()->create();
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $worker = $this->makeUserWithRole($company, 'worker', []); // no report.view

        $response = $this->actingAs($worker)->getJson("/api/v1/reports/suppliers/{$supplier->id}/history");
        $response->assertStatus(403);
    }
}
