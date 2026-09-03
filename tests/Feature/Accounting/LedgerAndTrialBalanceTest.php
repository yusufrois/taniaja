<?php

namespace Tests\Feature\Accounting;

use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Accounting\StandardChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LedgerAndTrialBalanceTest extends TestCase
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

    public function test_ledger_shows_running_balance_in_chronological_order(): void
    {
        $company = Company::factory()->create();
        app(StandardChartOfAccountsSeeder::class)->seedFor($company);
        $owner = $this->makeUserWithRole($company, 'owner', ['capital.create', 'accounting.view']);
        $kas = ChartOfAccount::where('company_id', $company->id)->where('code', '1100')->first();

        // Two capital injections, 3.000.000 then 2.000.000.
        $this->actingAs($owner)->postJson('/api/v1/capital-transactions', [
            'type' => 'owner_investment', 'date' => '2026-08-01', 'amount' => 3000000,
        ])->assertCreated();
        $this->actingAs($owner)->postJson('/api/v1/capital-transactions', [
            'type' => 'owner_investment', 'date' => '2026-08-05', 'amount' => 2000000,
        ])->assertCreated();

        $ledger = $this->actingAs($owner)->getJson("/api/v1/accounting/ledger/{$kas->id}");
        $ledger->assertOk();

        $this->assertCount(2, $ledger->json('lines'));
        $this->assertEquals(3000000.0, $ledger->json('lines.0.running_balance'));
        $this->assertEquals(5000000.0, $ledger->json('lines.1.running_balance')); // cumulative
        $this->assertEquals(5000000.0, $ledger->json('closing_balance'));
    }

    public function test_ledger_filters_by_date_range(): void
    {
        $company = Company::factory()->create();
        app(StandardChartOfAccountsSeeder::class)->seedFor($company);
        $owner = $this->makeUserWithRole($company, 'owner', ['capital.create', 'accounting.view']);
        $kas = ChartOfAccount::where('company_id', $company->id)->where('code', '1100')->first();

        $this->actingAs($owner)->postJson('/api/v1/capital-transactions', [
            'type' => 'owner_investment', 'date' => '2026-07-01', 'amount' => 1000000,
        ])->assertCreated();
        $this->actingAs($owner)->postJson('/api/v1/capital-transactions', [
            'type' => 'owner_investment', 'date' => '2026-08-01', 'amount' => 2000000,
        ])->assertCreated();

        $ledger = $this->actingAs($owner)->getJson("/api/v1/accounting/ledger/{$kas->id}?from=2026-08-01");
        $ledger->assertOk();
        $this->assertCount(1, $ledger->json('lines'));
        $this->assertEquals(2000000.0, $ledger->json('lines.0.debit'));
    }

    /**
     * Since JournalEntryService (Fase L1) refuses to create an
     * unbalanced entry in the first place, the Trial Balance across
     * ALL accounts should ALWAYS come out balanced — this test doubles
     * as an integrity check on that guarantee holding through several
     * different transaction types at once.
     */
    public function test_trial_balance_is_always_balanced_across_mixed_transactions(): void
    {
        $company = Company::factory()->create();
        app(StandardChartOfAccountsSeeder::class)->seedFor($company);
        $owner = $this->makeUserWithRole($company, 'owner', [
            'accounting.view', 'capital.create', 'debt.create', 'expense.create', 'purchase.create',
        ]);
        $category = \App\Models\ExpenseCategory::factory()->create(['company_id' => $company->id]);
        $supplier = \App\Models\Supplier::factory()->create(['company_id' => $company->id]);
        $crop = \App\Models\Crop::factory()->create(['company_id' => $company->id]);

        $this->actingAs($owner)->postJson('/api/v1/capital-transactions', [
            'type' => 'owner_investment', 'date' => now()->toDateString(), 'amount' => 10000000,
        ])->assertCreated();
        $this->actingAs($owner)->postJson('/api/v1/debts', [
            'creditor_name' => 'Bank', 'debt_date' => now()->toDateString(), 'amount' => 5000000,
        ])->assertCreated();
        $this->actingAs($owner)->postJson('/api/v1/expenses', [
            'expense_category_id' => $category->id, 'date' => now()->toDateString(), 'amount' => 750000,
        ])->assertCreated();
        $this->actingAs($owner)->postJson('/api/v1/purchases', [
            'supplier_id' => $supplier->id, 'crop_id' => $crop->id,
            'purchase_date' => now()->toDateString(), 'quantity' => 100, 'unit_price' => 4000,
        ])->assertCreated();

        $trialBalance = $this->actingAs($owner)->getJson('/api/v1/accounting/trial-balance');
        $trialBalance->assertOk();

        $this->assertTrue($trialBalance->json('is_balanced'));
        $this->assertEquals($trialBalance->json('total_debit'), $trialBalance->json('total_credit'));
    }

    public function test_trial_balance_omits_accounts_with_zero_balance(): void
    {
        $company = Company::factory()->create();
        app(StandardChartOfAccountsSeeder::class)->seedFor($company);
        $owner = $this->makeUserWithRole($company, 'owner', ['accounting.view', 'capital.create']);

        $this->actingAs($owner)->postJson('/api/v1/capital-transactions', [
            'type' => 'owner_investment', 'date' => now()->toDateString(), 'amount' => 1000000,
        ])->assertCreated();

        $trialBalance = $this->actingAs($owner)->getJson('/api/v1/accounting/trial-balance');
        $trialBalance->assertOk();

        // 16 accounts seeded, but only Kas + Modal Pemilik have activity.
        $this->assertCount(2, $trialBalance->json('accounts'));
    }

    public function test_supervisor_cannot_access_trial_balance(): void
    {
        $company = Company::factory()->create();
        app(StandardChartOfAccountsSeeder::class)->seedFor($company);
        $supervisor = $this->makeUserWithRole($company, 'supervisor', []);

        $response = $this->actingAs($supervisor)->getJson('/api/v1/accounting/trial-balance');
        $response->assertForbidden();
    }
}
