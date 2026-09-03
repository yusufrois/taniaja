<?php

namespace Tests\Feature\Accounting;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Accounting\StandardChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BalanceSheetAndIncomeStatementTest extends TestCase
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
     * The core guarantee: even WITHOUT any period-closing entry ever
     * being posted, the Balance Sheet must balance in real time —
     * proven here with a mix of capital, a sale (with revenue that's
     * never been "closed"), and an expense.
     */
    public function test_balance_sheet_balances_including_unclosed_current_earnings(): void
    {
        $company = Company::factory()->create();
        app(StandardChartOfAccountsSeeder::class)->seedFor($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $category = \App\Models\ExpenseCategory::factory()->create(['company_id' => $company->id]);
        $owner = $this->makeUserWithRole($company, 'owner', [
            'capital.create', 'sale.create', 'expense.create', 'accounting.view',
        ]);

        $this->actingAs($owner)->postJson('/api/v1/capital-transactions', [
            'type' => 'owner_investment', 'date' => now()->toDateString(), 'amount' => 10000000,
        ])->assertCreated();

        $this->actingAs($owner)->postJson('/api/v1/sales', [
            'customer_id' => $customer->id, 'date' => now()->toDateString(),
            'items' => [['description' => 'Melon', 'quantity' => 10, 'price' => 20000]], // revenue 200.000, no COGS (no stock_batch_id)
        ])->assertCreated();

        $this->actingAs($owner)->postJson('/api/v1/expenses', [
            'expense_category_id' => $category->id, 'date' => now()->toDateString(), 'amount' => 50000,
        ])->assertCreated();

        $response = $this->actingAs($owner)->getJson('/api/v1/accounting/balance-sheet');
        $response->assertOk();

        $this->assertTrue($response->json('is_balanced'));
        $this->assertEquals($response->json('total_assets'), round($response->json('total_liabilities') + $response->json('total_equity'), 2));

        // Current earnings (200.000 revenue - 50.000 expense = 150.000) shows as an equity line.
        $equity = collect($response->json('equity'));
        $this->assertTrue($equity->contains(fn ($e) => str_contains($e['name'], 'Laba Tahun Berjalan') && $e['balance'] == 150000.0));
    }

    public function test_income_statement_only_includes_transactions_within_the_period(): void
    {
        $company = Company::factory()->create();
        app(StandardChartOfAccountsSeeder::class)->seedFor($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $owner = $this->makeUserWithRole($company, 'owner', ['sale.create', 'accounting.view']);

        // Inside the period.
        $this->actingAs($owner)->postJson('/api/v1/sales', [
            'customer_id' => $customer->id, 'date' => '2026-08-15',
            'items' => [['description' => 'Melon Agustus', 'quantity' => 10, 'price' => 20000]],
        ])->assertCreated();

        // Outside the period (different month).
        $this->actingAs($owner)->postJson('/api/v1/sales', [
            'customer_id' => $customer->id, 'date' => '2026-07-10',
            'items' => [['description' => 'Melon Juli', 'quantity' => 5, 'price' => 20000]],
        ])->assertCreated();

        $response = $this->actingAs($owner)->getJson('/api/v1/accounting/income-statement?from=2026-08-01&to=2026-08-31');
        $response->assertOk();

        $this->assertEquals(200000.0, $response->json('total_revenue')); // only the August sale
        $this->assertEquals(200000.0, $response->json('net_income')); // no expenses in this test
    }

    public function test_supervisor_cannot_access_balance_sheet(): void
    {
        $company = Company::factory()->create();
        app(StandardChartOfAccountsSeeder::class)->seedFor($company);
        $supervisor = $this->makeUserWithRole($company, 'supervisor', []);

        $response = $this->actingAs($supervisor)->getJson('/api/v1/accounting/balance-sheet');
        $response->assertForbidden();
    }
}
