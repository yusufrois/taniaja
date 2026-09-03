<?php

namespace Tests\Feature\Reports;

use App\Livewire\Dashboard;
use App\Models\Company;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Roadmap tambahan (permintaan pengguna) — "beban yang Menunggu tidak
 * masuk hitungan laporan dulu". Both the Dashboard AND the formal
 * Laba Rugi report (ReportController::companyProfitLoss) now only
 * count Expense rows with a non-null approved_at.
 *
 * NOT changed by this: the accounting ledger (Fase L2 auto-posting)
 * still journals an Expense the moment it's created, regardless of
 * approval — that's a deliberate scope boundary (see README), not an
 * oversight.
 */
class ApprovedExpenseOnlyTest extends TestCase
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

    public function test_dashboard_total_expense_excludes_unapproved_expenses(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['report.view']);
        $category = ExpenseCategory::create(['company_id' => $company->id, 'name' => 'Pupuk']);

        Expense::create([
            'company_id' => $company->id, 'expense_category_id' => $category->id,
            'date' => now()->toDateString(), 'amount' => 500000, 'approved_at' => now(), 'approved_by' => $owner->id,
        ]);
        Expense::create([
            'company_id' => $company->id, 'expense_category_id' => $category->id,
            'date' => now()->toDateString(), 'amount' => 300000, // NOT approved
        ]);

        $component = Livewire::actingAs($owner)->test(Dashboard::class);

        $this->assertEquals(500000.0, $component->get('totalExpense'));
    }

    public function test_company_profit_loss_report_excludes_unapproved_expenses(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['report.view']);
        $category = ExpenseCategory::create(['company_id' => $company->id, 'name' => 'Pupuk']);

        Expense::create([
            'company_id' => $company->id, 'expense_category_id' => $category->id,
            'date' => now()->toDateString(), 'amount' => 200000, 'approved_at' => now(), 'approved_by' => $owner->id,
        ]);
        Expense::create([
            'company_id' => $company->id, 'expense_category_id' => $category->id,
            'date' => now()->toDateString(), 'amount' => 750000, // NOT approved
        ]);

        $response = $this->actingAs($owner)->getJson('/api/v1/reports/company/profit-loss');
        $response->assertOk();

        $this->assertEquals(200000.0, $response->json('production_cost_total'));
    }
}
