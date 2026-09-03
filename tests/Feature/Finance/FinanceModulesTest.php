<?php

namespace Tests\Feature\Finance;

use App\Models\Asset;
use App\Models\Company;
use App\Models\Crop;
use App\Models\Debt;
use App\Models\ExpenseCategory;
use App\Models\Greenhouse;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Season;
use App\Models\User;
use App\Models\Variety;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceModulesTest extends TestCase
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

    public function test_greenhouse_construction_cost_sums_assets_of_that_category(): void
    {
        $company = Company::factory()->create();
        $greenhouse = Greenhouse::factory()->create(['company_id' => $company->id]);

        Asset::create([
            'company_id' => $company->id,
            'greenhouse_id' => $greenhouse->id,
            'name' => 'Rangka Greenhouse',
            'category' => 'greenhouse_construction',
            'purchase_date' => now(),
            'value' => 15000000,
        ]);
        Asset::create([
            'company_id' => $company->id,
            'greenhouse_id' => $greenhouse->id,
            'name' => 'Plastik UV',
            'category' => 'greenhouse_construction',
            'purchase_date' => now(),
            'value' => 5000000,
        ]);
        // A different category on the SAME greenhouse must NOT be counted
        Asset::create([
            'company_id' => $company->id,
            'greenhouse_id' => $greenhouse->id,
            'name' => 'Pompa Air',
            'category' => 'pump',
            'purchase_date' => now(),
            'value' => 2000000,
        ]);

        $this->assertEquals(20000000.0, $greenhouse->fresh()->constructionCost());
    }

    public function test_supervisor_can_input_expense_but_not_approve(): void
    {
        $company = Company::factory()->create();
        $category = ExpenseCategory::factory()->create(['company_id' => $company->id]);
        $supervisor = $this->makeUserWithRole($company, 'supervisor', ['expense.create', 'expense.view']);

        $create = $this->actingAs($supervisor)->postJson('/api/v1/expenses', [
            'expense_category_id' => $category->id,
            'date' => now()->toDateString(),
            'amount' => 350000,
            'description' => 'Pupuk NPK',
        ]);
        $create->assertCreated();

        $expenseId = $create->json('data.id');

        $approve = $this->actingAs($supervisor)->postJson("/api/v1/expenses/{$expenseId}/approve");
        $approve->assertForbidden();
    }

    public function test_finance_can_approve_expense(): void
    {
        $company = Company::factory()->create();
        $category = ExpenseCategory::factory()->create(['company_id' => $company->id]);
        $finance = $this->makeUserWithRole($company, 'finance', ['expense.create', 'expense.view', 'expense.approve']);

        $create = $this->actingAs($finance)->postJson('/api/v1/expenses', [
            'expense_category_id' => $category->id,
            'date' => now()->toDateString(),
            'amount' => 500000,
        ]);
        $expenseId = $create->json('data.id');

        $approve = $this->actingAs($finance)->postJson("/api/v1/expenses/{$expenseId}/approve");
        $approve->assertOk();
        $approve->assertJsonPath('data.is_approved', true);
    }

    public function test_season_dashboard_total_cost_sums_its_expenses(): void
    {
        $company = Company::factory()->create();
        $greenhouse = Greenhouse::factory()->create(['company_id' => $company->id]);
        $crop = Crop::factory()->create(['company_id' => $company->id]);
        $variety = Variety::factory()->create(['company_id' => $company->id, 'crop_id' => $crop->id]);
        $category = ExpenseCategory::factory()->create(['company_id' => $company->id]);

        $season = Season::create([
            'company_id' => $company->id,
            'greenhouse_id' => $greenhouse->id,
            'crop_id' => $crop->id,
            'variety_id' => $variety->id,
            'season_name' => 'Musim Biaya',
            'planting_date' => now(),
            'status' => 'active',
        ]);

        $finance = $this->makeUserWithRole($company, 'finance', ['expense.create', 'season.view']);

        $this->actingAs($finance)->postJson('/api/v1/expenses', [
            'season_id' => $season->id,
            'expense_category_id' => $category->id,
            'date' => now()->toDateString(),
            'amount' => 2000000,
        ])->assertCreated();

        $this->actingAs($finance)->postJson('/api/v1/expenses', [
            'season_id' => $season->id,
            'expense_category_id' => $category->id,
            'date' => now()->toDateString(),
            'amount' => 500000,
        ])->assertCreated();

        $dashboard = $this->actingAs($finance)->getJson("/api/v1/seasons/{$season->id}/dashboard");
        $dashboard->assertOk();
        $dashboard->assertJsonPath('summary.total_cost', 2500000);
    }

    public function test_debt_payment_cannot_exceed_remaining_amount(): void
    {
        $company = Company::factory()->create();
        $debt = Debt::factory()->create(['company_id' => $company->id, 'amount' => 10000000]);
        $finance = $this->makeUserWithRole($company, 'finance', ['debt.create', 'debt.update', 'debt.view']);

        $overpay = $this->actingAs($finance)->postJson("/api/v1/debts/{$debt->id}/payments", [
            'payment_date' => now()->toDateString(),
            'amount' => 15000000, // more than the debt itself
        ]);
        $overpay->assertStatus(422);
        $overpay->assertJsonValidationErrors('amount');
    }

    public function test_debt_status_transitions_from_unpaid_to_partial_to_paid(): void
    {
        $company = Company::factory()->create();
        $debt = Debt::factory()->create(['company_id' => $company->id, 'amount' => 10000000, 'due_date' => now()->addDays(30)]);
        $finance = $this->makeUserWithRole($company, 'finance', ['debt.create', 'debt.update', 'debt.view']);

        $this->assertEquals('unpaid', $debt->fresh()->status);

        $this->actingAs($finance)->postJson("/api/v1/debts/{$debt->id}/payments", [
            'payment_date' => now()->toDateString(),
            'amount' => 4000000,
        ])->assertCreated();
        $this->assertEquals('partial', $debt->fresh()->status);

        $this->actingAs($finance)->postJson("/api/v1/debts/{$debt->id}/payments", [
            'payment_date' => now()->toDateString(),
            'amount' => 6000000,
        ])->assertCreated();
        $this->assertEquals('paid', $debt->fresh()->status);
        $this->assertEquals(0.0, $debt->fresh()->remaining);
    }
}
