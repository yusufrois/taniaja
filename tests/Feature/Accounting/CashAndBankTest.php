<?php

namespace Tests\Feature\Accounting;

use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Sale;
use App\Models\User;
use App\Services\Accounting\StandardChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashAndBankTest extends TestCase
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

    public function test_owner_can_add_a_custom_bank_account_beyond_the_standard_16(): void
    {
        $company = Company::factory()->create();
        app(StandardChartOfAccountsSeeder::class)->seedFor($company);
        $owner = $this->makeUserWithRole($company, 'owner', ['accounting.create']);

        $response = $this->actingAs($owner)->postJson('/api/v1/chart-of-accounts', [
            'code' => '1120', 'name' => 'Bank BCA', 'type' => 'asset',
        ]);

        $response->assertCreated();
        $this->assertEquals(17, ChartOfAccount::where('company_id', $company->id)->count());
    }

    /**
     * The core Fase L5 payoff: a payment posts to the SPECIFIC account
     * chosen, not always the generic default Kas.
     */
    public function test_sale_payment_posts_to_the_specifically_chosen_bank_account(): void
    {
        $company = Company::factory()->create();
        app(StandardChartOfAccountsSeeder::class)->seedFor($company);
        $bca = ChartOfAccount::create(['company_id' => $company->id, 'code' => '1120', 'name' => 'Bank BCA', 'type' => 'asset']);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $owner = $this->makeUserWithRole($company, 'owner', ['sale.create', 'sale.update']);

        $sale = $this->actingAs($owner)->postJson('/api/v1/sales', [
            'customer_id' => $customer->id, 'date' => now()->toDateString(),
            'items' => [['description' => 'Melon', 'quantity' => 10, 'price' => 20000]],
        ]);
        $saleId = $sale->json('data.id');

        $this->actingAs($owner)->postJson("/api/v1/sales/{$saleId}/payments", [
            'payment_date' => now()->toDateString(), 'amount' => 200000, 'chart_of_account_id' => $bca->id,
        ])->assertCreated();

        $entry = JournalEntry::where('reference_type', 'sale_payment')->with('lines.chartOfAccount')->first();
        $debitLine = $entry->lines->firstWhere('debit', '>', 0);
        $this->assertEquals('1120', $debitLine->chartOfAccount->code); // BCA, not the default Kas
    }

    public function test_sale_payment_falls_back_to_default_kas_when_no_account_chosen(): void
    {
        $company = Company::factory()->create();
        app(StandardChartOfAccountsSeeder::class)->seedFor($company);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $owner = $this->makeUserWithRole($company, 'owner', ['sale.create', 'sale.update']);

        $sale = $this->actingAs($owner)->postJson('/api/v1/sales', [
            'customer_id' => $customer->id, 'date' => now()->toDateString(),
            'items' => [['description' => 'Melon', 'quantity' => 5, 'price' => 20000]],
        ]);
        $saleId = $sale->json('data.id');

        // No chart_of_account_id given — backward compatible default.
        $this->actingAs($owner)->postJson("/api/v1/sales/{$saleId}/payments", [
            'payment_date' => now()->toDateString(), 'amount' => 100000,
        ])->assertCreated();

        $entry = JournalEntry::where('reference_type', 'sale_payment')->with('lines.chartOfAccount')->first();
        $debitLine = $entry->lines->firstWhere('debit', '>', 0);
        $this->assertEquals('1100', $debitLine->chartOfAccount->code); // default Kas
    }

    public function test_account_transfer_posts_a_balanced_entry(): void
    {
        $company = Company::factory()->create();
        app(StandardChartOfAccountsSeeder::class)->seedFor($company);
        $kas = ChartOfAccount::where('company_id', $company->id)->where('code', '1100')->first();
        $bank = ChartOfAccount::where('company_id', $company->id)->where('code', '1110')->first();
        $owner = $this->makeUserWithRole($company, 'owner', ['accounting.create', 'accounting.view']);

        $response = $this->actingAs($owner)->postJson('/api/v1/account-transfers', [
            'from_account_id' => $kas->id, 'to_account_id' => $bank->id,
            'amount' => 500000, 'date' => now()->toDateString(), 'notes' => 'Setor tunai ke bank',
        ]);
        $response->assertCreated();

        $entry = JournalEntry::where('reference_type', 'account_transfer')->with('lines')->first();
        $this->assertNotNull($entry);
        $this->assertCount(2, $entry->lines);
        $this->assertEquals(500000.0, (float) $entry->lines->sum('debit'));
        $this->assertEquals(500000.0, (float) $entry->lines->sum('credit'));
    }

    public function test_account_transfer_rejects_same_source_and_destination(): void
    {
        $company = Company::factory()->create();
        app(StandardChartOfAccountsSeeder::class)->seedFor($company);
        $kas = ChartOfAccount::where('company_id', $company->id)->where('code', '1100')->first();
        $owner = $this->makeUserWithRole($company, 'owner', ['accounting.create']);

        $response = $this->actingAs($owner)->postJson('/api/v1/account-transfers', [
            'from_account_id' => $kas->id, 'to_account_id' => $kas->id,
            'amount' => 100000, 'date' => now()->toDateString(),
        ]);

        $response->assertStatus(422);
    }

    /**
     * Ties L5 together with L3: after a transfer, each account's
     * ledger balance correctly reflects it — "Saldo per akun otomatis
     * dari Buku Besar", exactly as promised in the roadmap.
     */
    public function test_ledger_reflects_correct_balance_per_account_after_transfer(): void
    {
        $company = Company::factory()->create();
        app(StandardChartOfAccountsSeeder::class)->seedFor($company);
        $kas = ChartOfAccount::where('company_id', $company->id)->where('code', '1100')->first();
        $bank = ChartOfAccount::where('company_id', $company->id)->where('code', '1110')->first();
        $owner = $this->makeUserWithRole($company, 'owner', ['capital.create', 'accounting.create', 'accounting.view']);

        $this->actingAs($owner)->postJson('/api/v1/capital-transactions', [
            'type' => 'owner_investment', 'date' => now()->toDateString(), 'amount' => 2000000,
        ])->assertCreated();

        $this->actingAs($owner)->postJson('/api/v1/account-transfers', [
            'from_account_id' => $kas->id, 'to_account_id' => $bank->id,
            'amount' => 800000, 'date' => now()->toDateString(),
        ])->assertCreated();

        $kasLedger = $this->actingAs($owner)->getJson("/api/v1/accounting/ledger/{$kas->id}");
        $bankLedger = $this->actingAs($owner)->getJson("/api/v1/accounting/ledger/{$bank->id}");

        $this->assertEquals(1200000.0, $kasLedger->json('closing_balance')); // 2jt - 800rb
        $this->assertEquals(800000.0, $bankLedger->json('closing_balance'));
    }
}
