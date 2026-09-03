<?php

namespace Tests\Feature\Accounting;

use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\Crop;
use App\Models\Customer;
use App\Models\ExpenseCategory;
use App\Models\Grade;
use App\Models\JournalEntry;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Variety;
use App\Services\Accounting\StandardChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoPostingTest extends TestCase
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
     * The most important test in this whole file: a company WITHOUT
     * the Chart of Accounts set up (e.g. existed before Fase L) must
     * still be able to create a Sale normally — auto-posting failing
     * must NEVER block the underlying business transaction.
     */
    public function test_creating_a_sale_succeeds_even_without_chart_of_accounts(): void
    {
        $company = Company::factory()->create(); // deliberately NO ChartOfAccount seeded
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $owner = $this->makeUserWithRole($company, 'owner', ['sale.create']);

        $response = $this->actingAs($owner)->postJson('/api/v1/sales', [
            'customer_id' => $customer->id, 'date' => now()->toDateString(),
            'items' => [['description' => 'Melon', 'quantity' => 10, 'price' => 15000]],
        ]);

        $response->assertCreated(); // Sale itself succeeds regardless
        $this->assertDatabaseCount('journal_entries', 0); // but nothing got posted
    }

    public function test_creating_a_sale_with_cogs_posts_a_balanced_four_line_entry(): void
    {
        $company = Company::factory()->create();
        app(StandardChartOfAccountsSeeder::class)->seedFor($company);

        $greenhouse = \App\Models\Greenhouse::factory()->create(['company_id' => $company->id]);
        $crop = Crop::factory()->create(['company_id' => $company->id]);
        $variety = Variety::factory()->create(['company_id' => $company->id, 'crop_id' => $crop->id]);
        $grade = Grade::factory()->create(['company_id' => $company->id]);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $owner = $this->makeUserWithRole($company, 'owner', [
            'season.view', 'harvest.create', 'sale.create', 'sale.view', 'accounting.view', 'cost.view',
        ]);

        $season = \App\Models\Season::create([
            'company_id' => $company->id, 'greenhouse_id' => $greenhouse->id,
            'crop_id' => $crop->id, 'variety_id' => $variety->id,
            'season_name' => 'Musim L2', 'planting_date' => now()->subDays(30), 'status' => 'active',
        ]);

        // Harvest 100kg, cost basis 2.000.000 -> unit_cost 20.000/kg.
        \App\Models\Expense::create([
            'company_id' => $company->id, 'season_id' => $season->id,
            'expense_category_id' => ExpenseCategory::factory()->create(['company_id' => $company->id])->id,
            'date' => now()->toDateString(), 'amount' => 2000000,
        ]);
        $this->actingAs($owner)->postJson('/api/v1/harvests', [
            'season_id' => $season->id, 'harvest_date' => now()->toDateString(),
            'items' => [['grade_id' => $grade->id, 'weight' => 100]],
        ])->assertCreated();
        $batchId = $this->actingAs($owner)->getJson('/api/v1/stock-batches?source_type=own_harvest')->json('data.0.id');

        // Sell all 100kg at 30.000/kg -> revenue 3.000.000, cogs 2.000.000.
        $this->actingAs($owner)->postJson('/api/v1/sales', [
            'customer_id' => $customer->id, 'date' => now()->toDateString(),
            'items' => [['stock_batch_id' => $batchId, 'description' => 'Melon', 'quantity' => 100, 'price' => 30000]],
        ])->assertCreated();

        $entry = JournalEntry::where('reference_type', 'sale')->first();
        $this->assertNotNull($entry);
        $this->assertCount(4, $entry->lines);
        $this->assertEquals(5000000.0, (float) $entry->lines->sum('debit'));
        $this->assertEquals(5000000.0, (float) $entry->lines->sum('credit')); // balanced: (3jt+2jt) both sides
    }

    public function test_expense_posts_debit_beban_operasional_credit_kas(): void
    {
        $company = Company::factory()->create();
        app(StandardChartOfAccountsSeeder::class)->seedFor($company);
        $category = ExpenseCategory::factory()->create(['company_id' => $company->id]);
        $owner = $this->makeUserWithRole($company, 'owner', ['expense.create']);

        $this->actingAs($owner)->postJson('/api/v1/expenses', [
            'expense_category_id' => $category->id, 'date' => now()->toDateString(), 'amount' => 500000,
        ])->assertCreated();

        $entry = JournalEntry::where('reference_type', 'expense')->with('lines.chartOfAccount')->first();
        $this->assertNotNull($entry);

        $bebanLine = $entry->lines->firstWhere('debit', '>', 0);
        $kasLine = $entry->lines->firstWhere('credit', '>', 0);
        $this->assertEquals('5200', $bebanLine->chartOfAccount->code);
        $this->assertEquals('1100', $kasLine->chartOfAccount->code);
        $this->assertEquals(500000.0, (float) $bebanLine->debit);
        $this->assertEquals(500000.0, (float) $kasLine->credit);
    }

    public function test_purchase_posts_debit_persediaan_credit_hutang_usaha(): void
    {
        $company = Company::factory()->create();
        app(StandardChartOfAccountsSeeder::class)->seedFor($company);
        $supplier = Supplier::factory()->create(['company_id' => $company->id]);
        $crop = Crop::factory()->create(['company_id' => $company->id]);
        $owner = $this->makeUserWithRole($company, 'owner', ['purchase.create']);

        $this->actingAs($owner)->postJson('/api/v1/purchases', [
            'supplier_id' => $supplier->id, 'crop_id' => $crop->id,
            'purchase_date' => now()->toDateString(), 'quantity' => 50, 'unit_price' => 5000,
        ])->assertCreated();

        $entry = JournalEntry::where('reference_type', 'purchase')->with('lines.chartOfAccount')->first();
        $this->assertNotNull($entry);
        $persediaanLine = $entry->lines->firstWhere('debit', '>', 0);
        $hutangLine = $entry->lines->firstWhere('credit', '>', 0);
        $this->assertEquals('1300', $persediaanLine->chartOfAccount->code);
        $this->assertEquals('2100', $hutangLine->chartOfAccount->code);
        $this->assertEquals(250000.0, (float) $persediaanLine->debit); // 50 * 5000
    }

    public function test_owner_investment_posts_debit_kas_credit_modal(): void
    {
        $company = Company::factory()->create();
        app(StandardChartOfAccountsSeeder::class)->seedFor($company);
        $owner = $this->makeUserWithRole($company, 'owner', ['capital.create']);

        $this->actingAs($owner)->postJson('/api/v1/capital-transactions', [
            'type' => 'owner_investment', 'date' => now()->toDateString(), 'amount' => 10000000,
        ])->assertCreated();

        $entry = JournalEntry::where('reference_type', 'capital_transaction')->with('lines.chartOfAccount')->first();
        $kasLine = $entry->lines->firstWhere('debit', '>', 0);
        $modalLine = $entry->lines->firstWhere('credit', '>', 0);
        $this->assertEquals('1100', $kasLine->chartOfAccount->code);
        $this->assertEquals('3100', $modalLine->chartOfAccount->code);
    }

    public function test_withdrawal_posts_debit_modal_credit_kas(): void
    {
        $company = Company::factory()->create();
        app(StandardChartOfAccountsSeeder::class)->seedFor($company);
        $owner = $this->makeUserWithRole($company, 'owner', ['capital.create']);

        $this->actingAs($owner)->postJson('/api/v1/capital-transactions', [
            'type' => 'withdrawal', 'date' => now()->toDateString(), 'amount' => 2000000,
        ])->assertCreated();

        $entry = JournalEntry::where('reference_type', 'capital_transaction')->with('lines.chartOfAccount')->first();
        $modalLine = $entry->lines->firstWhere('debit', '>', 0);
        $kasLine = $entry->lines->firstWhere('credit', '>', 0);
        // Flipped compared to investment — money leaves the business.
        $this->assertEquals('3100', $modalLine->chartOfAccount->code);
        $this->assertEquals('1100', $kasLine->chartOfAccount->code);
    }

    public function test_debt_posts_debit_kas_credit_hutang_bank(): void
    {
        $company = Company::factory()->create();
        app(StandardChartOfAccountsSeeder::class)->seedFor($company);
        $owner = $this->makeUserWithRole($company, 'owner', ['debt.create']);

        $this->actingAs($owner)->postJson('/api/v1/debts', [
            'creditor_name' => 'Bank BRI', 'debt_date' => now()->toDateString(), 'amount' => 20000000,
        ])->assertCreated();

        $entry = JournalEntry::where('reference_type', 'debt')->with('lines.chartOfAccount')->first();
        $kasLine = $entry->lines->firstWhere('debit', '>', 0);
        $hutangLine = $entry->lines->firstWhere('credit', '>', 0);
        $this->assertEquals('1100', $kasLine->chartOfAccount->code);
        $this->assertEquals('2200', $hutangLine->chartOfAccount->code);
    }

    public function test_filtering_journal_entries_by_reference(): void
    {
        $company = Company::factory()->create();
        app(StandardChartOfAccountsSeeder::class)->seedFor($company);
        $owner = $this->makeUserWithRole($company, 'owner', ['capital.create', 'debt.create', 'accounting.view']);

        $this->actingAs($owner)->postJson('/api/v1/capital-transactions', [
            'type' => 'owner_investment', 'date' => now()->toDateString(), 'amount' => 1000000,
        ])->assertCreated();
        $this->actingAs($owner)->postJson('/api/v1/debts', [
            'creditor_name' => 'Bank X', 'debt_date' => now()->toDateString(), 'amount' => 500000,
        ])->assertCreated();

        $filtered = $this->actingAs($owner)->getJson('/api/v1/journal-entries?reference_type=debt');
        $filtered->assertOk();
        $this->assertCount(1, $filtered->json('data'));
        $this->assertEquals('debt', $filtered->json('data.0.reference_type'));
    }
}
