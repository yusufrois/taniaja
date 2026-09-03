<?php

namespace Tests\Feature\Dashboard;

use App\Livewire\Dashboard;
use App\Models\Company;
use App\Models\Crop;
use App\Models\Customer;
use App\Models\ExpenseCategory;
use App\Models\Grade;
use App\Models\Greenhouse;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\Season;
use App\Models\User;
use App\Models\Variety;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
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

    public function test_user_with_report_permission_sees_financial_dashboard(): void
    {
        $company = Company::factory()->create();
        Greenhouse::factory()->create(['company_id' => $company->id, 'status' => 'active']);
        $owner = $this->makeUserWithRole($company, 'owner', ['report.view', 'activity.view']);

        Livewire::actingAs($owner)
            ->test(Dashboard::class)
            ->assertSet('canViewReports', true)
            ->assertSet('activeGreenhouses', 1)
            ->assertSee('Total Revenue');
    }

    public function test_worker_without_report_permission_sees_operational_summary_only(): void
    {
        $company = Company::factory()->create();
        $worker = $this->makeUserWithRole($company, 'worker', ['activity.view']);

        $response = Livewire::actingAs($worker)
            ->test(Dashboard::class)
            ->assertSet('canViewReports', false)
            ->assertSet('canViewActivities', true)
            ->assertDontSee('Total Revenue')
            ->assertSee('Aktivitas Hari Ini');
    }

    public function test_user_with_no_relevant_permission_sees_empty_state(): void
    {
        $company = Company::factory()->create();
        $noAccess = $this->makeUserWithRole($company, 'finance', []); // deliberately stripped

        Livewire::actingAs($noAccess)
            ->test(Dashboard::class)
            ->assertSet('canViewReports', false)
            ->assertSet('canViewActivities', false)
            ->assertDontSee('Total Revenue')
            ->assertDontSee('Aktivitas Hari Ini');
    }

    /**
     * The important correctness check: net_profit on the dashboard must
     * use the SAME formula as Phase 8's report (Revenue - COGS via
     * SaleItem.cost), never a naive Revenue - all-Expense figure that
     * would double count production cost already embedded in COGS.
     *
     * To actually prove this (not just coincidentally match), a SECOND
     * season's expense is added that has NOT been harvested/sold yet —
     * it inflates total_expense but must NOT reduce net_profit, since
     * it hasn't become anyone's COGS. If the dashboard wrongly computed
     * revenue - total_expense, this test would catch it (1.000.000 vs
     * the wrong 500.000).
     *
     * Roadmap tambahan update: expenses created via the direct API now
     * start as "Menunggu" (unapproved) and are excluded from report
     * totals until approved — so both expenses here are explicitly
     * approved via POST /expenses/{id}/approve before asserting totals,
     * matching the real workflow a Finance/Owner would follow.
     */
    public function test_net_profit_uses_revenue_minus_cogs_not_revenue_minus_all_expense(): void
    {
        $company = Company::factory()->create();
        $greenhouse = Greenhouse::factory()->create(['company_id' => $company->id]);
        $crop = Crop::factory()->create(['company_id' => $company->id]);
        $variety = Variety::factory()->create(['company_id' => $company->id, 'crop_id' => $crop->id]);
        $grade = Grade::factory()->create(['company_id' => $company->id]);
        $category = ExpenseCategory::factory()->create(['company_id' => $company->id]);
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $seasonA = Season::create([
            'company_id' => $company->id,
            'greenhouse_id' => $greenhouse->id,
            'crop_id' => $crop->id,
            'variety_id' => $variety->id,
            'season_name' => 'Musim A (sudah jual)',
            'planting_date' => now()->subDays(30),
            'status' => 'active',
        ]);

        $seasonB = Season::create([
            'company_id' => $company->id,
            'greenhouse_id' => Greenhouse::factory()->create(['company_id' => $company->id])->id,
            'crop_id' => $crop->id,
            'variety_id' => $variety->id,
            'season_name' => 'Musim B (belum panen)',
            'planting_date' => now()->subDays(5),
            'status' => 'active',
        ]);

        $owner = $this->makeUserWithRole($company, 'owner', [
            'report.view', 'expense.create', 'expense.approve', 'harvest.create', 'harvest.view', 'sale.create', 'sale.view',
        ]);

        // Season A: cost 2.000.000, harvest 100kg (unit_cost 20.000/kg), sell all.
        $expenseA = $this->actingAs($owner)->postJson('/api/v1/expenses', [
            'season_id' => $seasonA->id,
            'expense_category_id' => $category->id,
            'date' => now()->toDateString(),
            'amount' => 2000000,
        ]);
        $expenseA->assertCreated();
        $this->actingAs($owner)->postJson('/api/v1/expenses/'.$expenseA->json('data.id').'/approve')->assertOk();

        $this->actingAs($owner)->postJson('/api/v1/harvests', [
            'season_id' => $seasonA->id,
            'harvest_date' => now()->toDateString(),
            'items' => [['grade_id' => $grade->id, 'weight' => 100]],
        ])->assertCreated();

        $batches = $this->actingAs($owner)->getJson('/api/v1/stock-batches?source_type=own_harvest');
        $batchId = $batches->json('data.0.id');

        // Revenue 3.000.000, COGS 2.000.000.
        $this->actingAs($owner)->postJson('/api/v1/sales', [
            'customer_id' => $customer->id,
            'date' => now()->toDateString(),
            'items' => [
                ['stock_batch_id' => $batchId, 'description' => 'Melon', 'quantity' => 100, 'price' => 30000],
            ],
        ])->assertCreated();

        // Season B: cost 500.000, NOTHING harvested/sold yet — must NOT
        // reduce net_profit, since it hasn't become anyone's COGS.
        $expenseB = $this->actingAs($owner)->postJson('/api/v1/expenses', [
            'season_id' => $seasonB->id,
            'expense_category_id' => $category->id,
            'date' => now()->toDateString(),
            'amount' => 500000,
        ]);
        $expenseB->assertCreated();
        $this->actingAs($owner)->postJson('/api/v1/expenses/'.$expenseB->json('data.id').'/approve')->assertOk();

        Livewire::actingAs($owner)
            ->test(Dashboard::class)
            ->assertSet('totalRevenue', 3000000.0)
            ->assertSet('totalExpense', 2500000.0) // 2.000.000 + 500.000
            // Correct: revenue(3.000.000) - cogs(2.000.000) = 1.000.000.
            // A WRONG revenue-minus-total-expense calc would give 500.000
            // instead — this assertion would fail if that bug existed.
            ->assertSet('netProfit', 1000000.0);
    }

    public function test_operational_summary_counts_today_and_overdue_schedules(): void
    {
        $company = Company::factory()->create();
        $greenhouse = Greenhouse::factory()->create(['company_id' => $company->id]);
        $crop = Crop::factory()->create(['company_id' => $company->id]);
        $variety = Variety::factory()->create(['company_id' => $company->id, 'crop_id' => $crop->id]);

        $season = Season::create([
            'company_id' => $company->id,
            'greenhouse_id' => $greenhouse->id,
            'crop_id' => $crop->id,
            'variety_id' => $variety->id,
            'season_name' => 'Musim Jadwal',
            'planting_date' => now(),
            'status' => 'active',
        ]);

        Schedule::create([
            'company_id' => $company->id,
            'season_id' => $season->id,
            'scheduled_date' => now()->toDateString(),
            'activity_name' => 'Tanam',
            'category' => 'tanam',
            'status' => 'pending',
        ]);
        Schedule::create([
            'company_id' => $company->id,
            'season_id' => $season->id,
            'scheduled_date' => now()->subDays(5)->toDateString(),
            'activity_name' => 'Pemupukan Terlambat',
            'category' => 'pemupukan',
            'status' => 'pending',
        ]);

        $worker = $this->makeUserWithRole($company, 'worker', ['activity.view']);

        Livewire::actingAs($worker)
            ->test(Dashboard::class)
            ->assertSet('todayActivitiesCount', 1)
            ->assertSet('overdueActivitiesCount', 1);
    }
}
