<?php

namespace Tests\Feature\Accounting;

use App\Models\Company;
use App\Models\Crop;
use App\Models\Customer;
use App\Models\Grade;
use App\Models\Greenhouse;
use App\Models\JournalEntry;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Season;
use App\Models\User;
use App\Models\Variety;
use App\Services\Accounting\StandardChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuickSaleAutoPostingTest extends TestCase
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
     * Closes the gap found while fixing the Sale COGS-timing bug:
     * Phase 6's quick-sale flow (StockBatchSale) was never wired into
     * accounting posting at all until this fix.
     */
    public function test_quick_sale_posts_a_balanced_four_line_entry_with_cogs(): void
    {
        $company = Company::factory()->create();
        app(StandardChartOfAccountsSeeder::class)->seedFor($company);

        $greenhouse = Greenhouse::factory()->create(['company_id' => $company->id]);
        $crop = Crop::factory()->create(['company_id' => $company->id]);
        $variety = Variety::factory()->create(['company_id' => $company->id, 'crop_id' => $crop->id]);
        $grade = Grade::factory()->create(['company_id' => $company->id]);
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $owner = $this->makeUserWithRole($company, 'owner', [
            'season.view', 'harvest.create', 'sale.create', 'sale.view',
        ]);

        $season = Season::create([
            'company_id' => $company->id, 'greenhouse_id' => $greenhouse->id,
            'crop_id' => $crop->id, 'variety_id' => $variety->id,
            'season_name' => 'Musim Quick Sale', 'planting_date' => now()->subDays(30), 'status' => 'active',
        ]);

        // Harvest 50kg, cost basis 500.000 -> unit_cost 10.000/kg.
        \App\Models\Expense::create([
            'company_id' => $company->id, 'season_id' => $season->id,
            'expense_category_id' => \App\Models\ExpenseCategory::factory()->create(['company_id' => $company->id])->id,
            'date' => now()->toDateString(), 'amount' => 500000,
        ]);
        $this->actingAs($owner)->postJson('/api/v1/harvests', [
            'season_id' => $season->id, 'harvest_date' => now()->toDateString(),
            'items' => [['grade_id' => $grade->id, 'weight' => 50]],
        ])->assertCreated();
        $batchId = $this->actingAs($owner)->getJson('/api/v1/stock-batches?source_type=own_harvest')->json('data.0.id');

        // Quick-sell all 50kg at 15.000/kg -> revenue 750.000, cogs 500.000.
        $this->actingAs($owner)->postJson("/api/v1/stock-batches/{$batchId}/sell", [
            'customer_id' => $customer->id, 'quantity_sold' => 50, 'sale_price_per_unit' => 15000,
        ])->assertCreated();

        $entry = JournalEntry::where('reference_type', 'stock_batch_sale')->first();
        $this->assertNotNull($entry);
        $this->assertCount(4, $entry->lines);
        $this->assertEquals(1250000.0, (float) $entry->lines->sum('debit')); // 750rb + 500rb
        $this->assertEquals(1250000.0, (float) $entry->lines->sum('credit'));
    }
}
