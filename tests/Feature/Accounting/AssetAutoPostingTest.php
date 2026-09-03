<?php

namespace Tests\Feature\Accounting;

use App\Livewire\Asset\Manage;
use App\Livewire\ChartOfAccount\Manage as ChartOfAccountManage;
use App\Models\Asset;
use App\Models\Company;
use App\Models\JournalEntry;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Accounting\AccountingReportService;
use App\Services\Accounting\StandardChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Bug report #6 — "saat input aset tetap apakah akan otomatis
 * mencatat pengeluaran dan mengurangi saldo, tapi didalamnya tidak
 * ada bagan akun". Confirmed gap: Modal/Hutang/Beban already
 * auto-posted, Asset never did. Fixed via AssetObserver + postAsset().
 */
class AssetAutoPostingTest extends TestCase
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

    public function test_creating_an_asset_posts_a_balanced_journal_entry_debiting_aset_tetap(): void
    {
        $company = Company::factory()->create();
        app(StandardChartOfAccountsSeeder::class)->seedFor($company);
        $owner = $this->makeUserWithRole($company, 'owner', ['asset.view', 'asset.create']);

        Livewire::actingAs($owner)
            ->test(Manage::class)
            ->call('openCreate')
            ->set('name', 'Pompa Air Sumur Bor')
            ->set('category', 'Peralatan')
            ->set('purchase_date', now()->toDateString())
            ->set('value', 5000000)
            ->call('save')
            ->assertHasNoErrors();

        $asset = Asset::where('name', 'Pompa Air Sumur Bor')->first();
        $entry = JournalEntry::where('reference_type', 'asset')->where('reference_id', $asset->id)->first();

        $this->assertNotNull($entry);
        $totalDebit = $entry->lines()->sum('debit');
        $totalCredit = $entry->lines()->sum('credit');
        $this->assertEquals(5000000.0, $totalDebit);
        $this->assertEquals($totalDebit, $totalCredit); // always-balanced double-entry

        $trialBalance = app(AccountingReportService::class)->trialBalance($company->id);
        $asetTetapRow = collect($trialBalance['accounts'])->firstWhere('code', '1400');
        $this->assertEquals(5000000.0, $asetTetapRow['debit']);
    }

    /** Deleting an Asset voids its journal too — same fix as bug #7, now covering Asset. */
    public function test_deleting_an_asset_voids_its_journal_entry(): void
    {
        $company = Company::factory()->create();
        app(StandardChartOfAccountsSeeder::class)->seedFor($company);
        $owner = $this->makeUserWithRole($company, 'owner', ['asset.view', 'asset.delete']);
        $asset = Asset::create([
            'company_id' => $company->id, 'name' => 'Terpal UV', 'category' => 'Peralatan',
            'purchase_date' => now()->toDateString(), 'value' => 800000, 'status' => 'active',
        ]);

        $this->assertDatabaseHas('journal_entries', ['reference_type' => 'asset', 'reference_id' => $asset->id]);

        Livewire::actingAs($owner)->test(Manage::class)->call('confirmDelete', $asset->id)->call('delete');

        $this->assertSoftDeleted('journal_entries', ['reference_type' => 'asset', 'reference_id' => $asset->id]);
    }

    /** Assets recorded before Chart of Accounts existed can be backfilled, same as Expense/Debt (#7's feature). */
    public function test_asset_recorded_before_chart_of_accounts_gets_posted_by_backfill(): void
    {
        $company = Company::factory()->create(); // no Chart of Accounts yet
        $owner = $this->makeUserWithRole($company, 'owner', ['accounting.view', 'accounting.create']);

        $asset = Asset::create([
            'company_id' => $company->id, 'name' => 'Genset', 'category' => 'Peralatan',
            'purchase_date' => now()->subDays(10)->toDateString(), 'value' => 12000000, 'status' => 'active',
        ]);
        $this->assertDatabaseMissing('journal_entries', ['reference_type' => 'asset', 'reference_id' => $asset->id]);

        Livewire::actingAs($owner)->test(ChartOfAccountManage::class)->call('seedDefaults');
        Livewire::actingAs($owner)
            ->test(ChartOfAccountManage::class)
            ->call('backfillOldTransactions')
            ->assertSet('backfillResult.asset', 1);

        $this->assertDatabaseHas('journal_entries', ['reference_type' => 'asset', 'reference_id' => $asset->id]);
    }
}
