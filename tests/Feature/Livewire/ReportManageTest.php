<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Report\Manage;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Accounting\JournalEntryService;
use App\Services\Accounting\StandardChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ReportManageTest extends TestCase
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

    private function postCapitalInjection(Company $company, User $owner, float $amount): void
    {
        $companyAccounts = ChartOfAccount::where('company_id', $company->id);
        $kas = (clone $companyAccounts)->where('code', '1100')->first();
        $modal = (clone $companyAccounts)->where('code', '3100')->first();

        app(JournalEntryService::class)->create(
            ['date' => now()->toDateString(), 'description' => 'Setoran modal'],
            [
                ['chart_of_account_id' => $kas->id, 'debit' => $amount, 'credit' => 0],
                ['chart_of_account_id' => $modal->id, 'debit' => 0, 'credit' => $amount],
            ],
            $owner
        );
    }

    public function test_trial_balance_tab_shows_balanced_totals(): void
    {
        $company = Company::factory()->create();
        app(StandardChartOfAccountsSeeder::class)->seedFor($company);
        $owner = $this->makeUserWithRole($company, 'owner', ['accounting.view']);
        $this->postCapitalInjection($company, $owner, 5000000);

        $component = Livewire::actingAs($owner)->test(Manage::class)->call('setTab', 'trial-balance');

        $trialBalance = $component->viewData('trialBalance');
        $this->assertEquals(5000000.0, $trialBalance['total_debit']);
        $this->assertTrue($trialBalance['is_balanced']);
    }

    public function test_balance_sheet_tab_shows_assets_equal_liabilities_plus_equity(): void
    {
        $company = Company::factory()->create();
        app(StandardChartOfAccountsSeeder::class)->seedFor($company);
        $owner = $this->makeUserWithRole($company, 'owner', ['accounting.view']);
        $this->postCapitalInjection($company, $owner, 3000000);

        $component = Livewire::actingAs($owner)->test(Manage::class)->call('setTab', 'balance-sheet');

        $balanceSheet = $component->viewData('balanceSheet');
        $this->assertEquals(3000000.0, $balanceSheet['total_assets']);
        $this->assertTrue($balanceSheet['is_balanced']);
    }

    public function test_income_statement_tab_is_the_default_and_computes_net_income(): void
    {
        $company = Company::factory()->create();
        app(StandardChartOfAccountsSeeder::class)->seedFor($company);
        $owner = $this->makeUserWithRole($company, 'owner', ['accounting.view']);

        $companyAccounts = ChartOfAccount::where('company_id', $company->id);
        $kas = (clone $companyAccounts)->where('code', '1100')->first();
        $pendapatan = (clone $companyAccounts)->where('code', '4100')->first();

        app(JournalEntryService::class)->create(
            ['date' => now()->toDateString(), 'description' => 'Penjualan'],
            [
                ['chart_of_account_id' => $kas->id, 'debit' => 1500000, 'credit' => 0],
                ['chart_of_account_id' => $pendapatan->id, 'debit' => 0, 'credit' => 1500000],
            ],
            $owner
        );

        $component = Livewire::actingAs($owner)->test(Manage::class)->assertSet('activeTab', 'income-statement');

        $incomeStatement = $component->viewData('incomeStatement');
        $this->assertEquals(1500000.0, $incomeStatement['total_revenue']);
        $this->assertEquals(1500000.0, $incomeStatement['net_income']);
    }

    public function test_worker_without_permission_cannot_view_reports(): void
    {
        $company = Company::factory()->create();
        $worker = $this->makeUserWithRole($company, 'worker', []);

        $this->assertTrue($worker->cannot('viewAny', ChartOfAccount::class));
    }
}
