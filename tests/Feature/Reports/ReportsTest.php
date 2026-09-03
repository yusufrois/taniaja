<?php

namespace Tests\Feature\Reports;

use App\Models\ActivityTemplate;
use App\Models\ActivityTemplateItem;
use App\Models\Company;
use App\Models\Crop;
use App\Models\Customer;
use App\Models\ExpenseCategory;
use App\Models\Grade;
use App\Models\Greenhouse;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Season;
use App\Models\User;
use App\Models\Variety;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsTest extends TestCase
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

    private function makeSeason(Company $company, Greenhouse $greenhouse, Crop $crop, Variety $variety): Season
    {
        return Season::create([
            'company_id' => $company->id,
            'greenhouse_id' => $greenhouse->id,
            'crop_id' => $crop->id,
            'variety_id' => $variety->id,
            'season_name' => 'Musim Report Test',
            'planting_date' => now()->subDays(60),
            'status' => 'active',
        ]);
    }

    /**
     * Roadmap tambahan (#1: "Beban Menunggu tidak masuk laporan")
     * touched season HPP/profit-loss too (previously only Dashboard
     * and company profit-loss) — so any Expense a report test expects
     * to COUNT must now be explicitly approved via POST
     * /expenses/{id}/approve, same as the real workflow a Finance/
     * Owner would follow. Requires the 'expense.approve' permission.
     */
    private function createAndApproveExpense(User $owner, array $payload): void
    {
        $expense = $this->actingAs($owner)->postJson('/api/v1/expenses', $payload);
        $expense->assertCreated();
        $this->actingAs($owner)->postJson('/api/v1/expenses/'.$expense->json('data.id').'/approve')->assertOk();
    }

    public function test_user_without_report_permission_gets_403(): void
    {
        $company = Company::factory()->create();
        $noPermUser = $this->makeUserWithRole($company, 'worker', []); // deliberately no permissions

        $response = $this->actingAs($noPermUser)->getJson('/api/v1/reports/company/profit-loss');
        $response->assertForbidden();
    }

    public function test_season_hpp_divides_total_cost_by_total_harvest_weight(): void
    {
        $company = Company::factory()->create();
        $greenhouse = Greenhouse::factory()->create(['company_id' => $company->id]);
        $crop = Crop::factory()->create(['company_id' => $company->id]);
        $variety = Variety::factory()->create(['company_id' => $company->id, 'crop_id' => $crop->id]);
        $grade = Grade::factory()->create(['company_id' => $company->id]);
        $category = ExpenseCategory::factory()->create(['company_id' => $company->id]);
        $season = $this->makeSeason($company, $greenhouse, $crop, $variety);

        $owner = $this->makeUserWithRole($company, 'owner', ['expense.create', 'expense.approve', 'harvest.create', 'report.view']);

        $this->createAndApproveExpense($owner, [
            'season_id' => $season->id,
            'expense_category_id' => $category->id,
            'date' => now()->toDateString(),
            'amount' => 2000000,
        ]);

        $this->actingAs($owner)->postJson('/api/v1/harvests', [
            'season_id' => $season->id,
            'harvest_date' => now()->toDateString(),
            'items' => [['grade_id' => $grade->id, 'weight' => 100]],
        ])->assertCreated();

        $hpp = $this->actingAs($owner)->getJson("/api/v1/reports/seasons/{$season->id}/hpp");
        $hpp->assertOk();

        $this->assertEquals(2000000, $hpp->json('total_cost'));
        $this->assertEquals(100, $hpp->json('total_harvest_kg'));
        $this->assertEquals(20000, $hpp->json('hpp_per_kg'));
    }

    public function test_season_hpp_is_null_when_nothing_harvested_yet(): void
    {
        $company = Company::factory()->create();
        $greenhouse = Greenhouse::factory()->create(['company_id' => $company->id]);
        $crop = Crop::factory()->create(['company_id' => $company->id]);
        $variety = Variety::factory()->create(['company_id' => $company->id, 'crop_id' => $crop->id]);
        $season = $this->makeSeason($company, $greenhouse, $crop, $variety);

        $owner = $this->makeUserWithRole($company, 'owner', ['report.view']);

        $hpp = $this->actingAs($owner)->getJson("/api/v1/reports/seasons/{$season->id}/hpp");
        $hpp->assertOk();

        $this->assertEquals(0, $hpp->json('total_cost'));
        $this->assertEquals(0, $hpp->json('total_harvest_kg'));
        $this->assertNull($hpp->json('hpp_per_kg')); // must NOT divide by zero
    }

    /**
     * The most important test in this phase: proves COGS is NOT double
     * counted against production cost. Season cost = 2.000.000, harvest
     * 100kg (unit_cost = 20.000/kg), sell all 100kg at 30.000/kg.
     * Expected: revenue 3.000.000, cogs 2.000.000, gross_profit 1.000.000.
     */
    public function test_season_profit_loss_does_not_double_count_cogs(): void
    {
        $company = Company::factory()->create();
        $greenhouse = Greenhouse::factory()->create(['company_id' => $company->id]);
        $crop = Crop::factory()->create(['company_id' => $company->id]);
        $variety = Variety::factory()->create(['company_id' => $company->id, 'crop_id' => $crop->id]);
        $grade = Grade::factory()->create(['company_id' => $company->id]);
        $category = ExpenseCategory::factory()->create(['company_id' => $company->id]);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $season = $this->makeSeason($company, $greenhouse, $crop, $variety);

        $owner = $this->makeUserWithRole($company, 'owner', [
            'expense.create', 'expense.approve', 'harvest.create', 'harvest.view', 'sale.create', 'sale.view', 'report.view',
        ]);

        $this->createAndApproveExpense($owner, [
            'season_id' => $season->id,
            'expense_category_id' => $category->id,
            'date' => now()->toDateString(),
            'amount' => 2000000,
        ]);

        $this->actingAs($owner)->postJson('/api/v1/harvests', [
            'season_id' => $season->id,
            'harvest_date' => now()->toDateString(),
            'items' => [['grade_id' => $grade->id, 'weight' => 100]],
        ])->assertCreated();

        $batches = $this->actingAs($owner)->getJson('/api/v1/stock-batches?source_type=own_harvest');
        $batchId = $batches->json('data.0.id');

        $this->actingAs($owner)->postJson('/api/v1/sales', [
            'customer_id' => $customer->id,
            'season_id' => $season->id,
            'date' => now()->toDateString(),
            'items' => [
                ['stock_batch_id' => $batchId, 'description' => 'Melon', 'quantity' => 100, 'price' => 30000],
            ],
        ])->assertCreated();

        $pl = $this->actingAs($owner)->getJson("/api/v1/reports/seasons/{$season->id}/profit-loss");
        $pl->assertOk();

        $this->assertEquals(3000000, $pl->json('revenue'));
        $this->assertEquals(2000000, $pl->json('cogs'));
        $this->assertEquals(1000000, $pl->json('gross_profit'));
        $this->assertEquals(1000000, $pl->json('net_profit'));
        // Everything harvested was sold, so nothing should remain in stock.
        $this->assertEquals(0, $pl->json('unsold_inventory_value'));
    }

    public function test_season_profit_loss_shows_unsold_inventory_value_for_stock_not_yet_sold(): void
    {
        $company = Company::factory()->create();
        $greenhouse = Greenhouse::factory()->create(['company_id' => $company->id]);
        $crop = Crop::factory()->create(['company_id' => $company->id]);
        $variety = Variety::factory()->create(['company_id' => $company->id, 'crop_id' => $crop->id]);
        $grade = Grade::factory()->create(['company_id' => $company->id]);
        $category = ExpenseCategory::factory()->create(['company_id' => $company->id]);
        $season = $this->makeSeason($company, $greenhouse, $crop, $variety);

        $owner = $this->makeUserWithRole($company, 'owner', ['expense.create', 'expense.approve', 'harvest.create', 'report.view']);

        $this->createAndApproveExpense($owner, [
            'season_id' => $season->id,
            'expense_category_id' => $category->id,
            'date' => now()->toDateString(),
            'amount' => 2000000,
        ]);

        // Harvest 100kg, unit_cost = 20.000/kg — nothing sold yet.
        $this->actingAs($owner)->postJson('/api/v1/harvests', [
            'season_id' => $season->id,
            'harvest_date' => now()->toDateString(),
            'items' => [['grade_id' => $grade->id, 'weight' => 100]],
        ])->assertCreated();

        $pl = $this->actingAs($owner)->getJson("/api/v1/reports/seasons/{$season->id}/profit-loss");
        $pl->assertOk();

        $this->assertEquals(0, $pl->json('revenue'));
        $this->assertEquals(0, $pl->json('cogs'));
        // Full 100kg still sitting in stock at 20.000/kg = 2.000.000
        $this->assertEquals(2000000, $pl->json('unsold_inventory_value'));
    }

    public function test_greenhouse_performance_includes_construction_cost_and_roi(): void
    {
        $company = Company::factory()->create();
        $greenhouse = Greenhouse::factory()->create(['company_id' => $company->id]);
        $crop = Crop::factory()->create(['company_id' => $company->id]);
        $variety = Variety::factory()->create(['company_id' => $company->id, 'crop_id' => $crop->id]);
        $grade = Grade::factory()->create(['company_id' => $company->id]);
        $category = ExpenseCategory::factory()->create(['company_id' => $company->id]);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $season = $this->makeSeason($company, $greenhouse, $crop, $variety);

        $owner = $this->makeUserWithRole($company, 'owner', [
            'asset.create', 'expense.create', 'expense.approve', 'harvest.create', 'harvest.view',
            'sale.create', 'sale.view', 'report.view',
        ]);

        // Construction cost: Rp10.000.000
        $this->actingAs($owner)->postJson('/api/v1/assets', [
            'greenhouse_id' => $greenhouse->id,
            'name' => 'Rangka GH',
            'category' => 'greenhouse_construction',
            'purchase_date' => now()->toDateString(),
            'value' => 10000000,
        ])->assertCreated();

        $this->createAndApproveExpense($owner, [
            'season_id' => $season->id,
            'expense_category_id' => $category->id,
            'date' => now()->toDateString(),
            'amount' => 2000000,
        ]);

        $this->actingAs($owner)->postJson('/api/v1/harvests', [
            'season_id' => $season->id,
            'harvest_date' => now()->toDateString(),
            'items' => [['grade_id' => $grade->id, 'weight' => 100]],
        ])->assertCreated();

        $batches = $this->actingAs($owner)->getJson('/api/v1/stock-batches?source_type=own_harvest');
        $batchId = $batches->json('data.0.id');

        // Sell for a gross profit of Rp1.000.000
        $this->actingAs($owner)->postJson('/api/v1/sales', [
            'customer_id' => $customer->id,
            'greenhouse_id' => $greenhouse->id,
            'date' => now()->toDateString(),
            'items' => [
                ['stock_batch_id' => $batchId, 'description' => 'Melon', 'quantity' => 100, 'price' => 30000],
            ],
        ])->assertCreated();

        $performance = $this->actingAs($owner)->getJson("/api/v1/reports/greenhouses/{$greenhouse->id}/performance");
        $performance->assertOk();

        $this->assertEquals(10000000, $performance->json('construction_cost'));
        $this->assertEquals(3000000, $performance->json('revenue'));
        $this->assertEquals(2000000, $performance->json('cogs'));
        $this->assertEquals(1000000, $performance->json('gross_profit'));
        // ROI = 1.000.000 / 10.000.000 * 100 = 10%
        $this->assertEquals(10.0, $performance->json('roi_percent'));
    }

    public function test_today_and_overdue_activities_are_reported_correctly(): void
    {
        $template = ActivityTemplate::factory()->create();
        $company = Company::find($template->company_id);

        // HST 0 -> due today; HST -10 (i.e. 10 days before planting_date,
        // guaranteed in the past) -> overdue.
        ActivityTemplateItem::create([
            'company_id' => $template->company_id,
            'activity_template_id' => $template->id,
            'hst' => 0,
            'activity_name' => 'Tanam',
            'category' => 'tanam',
        ]);
        ActivityTemplateItem::create([
            'company_id' => $template->company_id,
            'activity_template_id' => $template->id,
            'hst' => -10,
            'activity_name' => 'Persiapan Lahan (terlambat)',
            'category' => 'lain',
        ]);

        $greenhouse = Greenhouse::factory()->create(['company_id' => $company->id]);
        $season = Season::create([
            'company_id' => $company->id,
            'greenhouse_id' => $greenhouse->id,
            'crop_id' => $template->variety->crop_id,
            'variety_id' => $template->variety_id,
            'season_name' => 'Musim Notif Test',
            'planting_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $owner = $this->makeUserWithRole($company, 'owner', ['season.update', 'report.view']);
        $this->actingAs($owner)->postJson("/api/v1/seasons/{$season->id}/generate-schedule")->assertOk();

        $report = $this->actingAs($owner)->getJson('/api/v1/reports/activities/today');
        $report->assertOk();

        $this->assertCount(1, $report->json('today'));
        $this->assertCount(1, $report->json('overdue'));
        $this->assertEquals('Tanam', $report->json('today.0.activity_name'));
        $this->assertEquals('Persiapan Lahan (terlambat)', $report->json('overdue.0.activity_name'));
    }
}
