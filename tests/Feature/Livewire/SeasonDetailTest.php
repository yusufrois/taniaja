<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Season\Detail;
use App\Models\Company;
use App\Models\Crop;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Greenhouse;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Season;
use App\Models\User;
use App\Models\Variety;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Roadmap tambahan — "Detail Musim Tanam" (laporan laba rugi +
 * rincian pengeluaran per musim per greenhouse), dinaikkan
 * prioritasnya berdasarkan diskusi dengan pengguna tentang kebutuhan
 * evaluasi musim yang sudah selesai.
 */
class SeasonDetailTest extends TestCase
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

    public function test_owner_sees_profit_loss_and_expense_detail_for_a_season(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['season.view', 'cost.view']);
        $greenhouse = Greenhouse::create(['company_id' => $company->id, 'code' => 'GH-01', 'name' => 'GH A', 'status' => 'active']);
        $crop = Crop::create(['company_id' => $company->id, 'name' => 'Melon']);
        $variety = Variety::create(['company_id' => $company->id, 'crop_id' => $crop->id, 'name' => 'Golden']);
        $season = Season::create([
            'company_id' => $company->id, 'greenhouse_id' => $greenhouse->id,
            'crop_id' => $crop->id, 'variety_id' => $variety->id,
            'season_name' => 'Musim Selesai', 'planting_date' => now()->subDays(60)->toDateString(), 'status' => 'completed',
        ]);
        $category = ExpenseCategory::create(['company_id' => $company->id, 'name' => 'Pupuk']);
        Expense::create([
            'company_id' => $company->id, 'season_id' => $season->id, 'expense_category_id' => $category->id,
            'date' => now()->subDays(50)->toDateString(), 'amount' => 500000, 'approved_at' => now(), 'approved_by' => $owner->id,
        ]);
        Expense::create([
            'company_id' => $company->id, 'season_id' => $season->id, 'expense_category_id' => $category->id,
            'date' => now()->subDays(40)->toDateString(), 'amount' => 300000, // NOT approved
        ]);

        $component = Livewire::actingAs($owner)->test(Detail::class, ['season' => $season]);

        $profitLoss = $component->viewData('profitLoss');
        $expenses = $component->viewData('expenses');

        // Only the APPROVED expense counts toward production cost (matches #1's rule).
        $this->assertEquals(500000.0, $profitLoss['production_cost_total']);
        // But BOTH show up in the detail list (transparency — user sees what's still pending).
        $this->assertCount(2, $expenses);
    }

    public function test_worker_without_cost_view_does_not_see_financial_figures(): void
    {
        $company = Company::factory()->create();
        $worker = $this->makeUserWithRole($company, 'worker', ['season.view']); // no cost.view
        $greenhouse = Greenhouse::create(['company_id' => $company->id, 'code' => 'GH-01', 'name' => 'GH A', 'status' => 'active']);
        $crop = Crop::create(['company_id' => $company->id, 'name' => 'Melon']);
        $variety = Variety::create(['company_id' => $company->id, 'crop_id' => $crop->id, 'name' => 'Golden']);
        $season = Season::create([
            'company_id' => $company->id, 'greenhouse_id' => $greenhouse->id,
            'crop_id' => $crop->id, 'variety_id' => $variety->id,
            'season_name' => 'Musim Rahasia', 'planting_date' => now()->toDateString(), 'status' => 'active',
        ]);

        Livewire::actingAs($worker)
            ->test(Detail::class, ['season' => $season])
            ->assertDontSee('Pendapatan')
            ->assertSee('tidak punya akses');
    }

    public function test_worker_without_season_view_cannot_open_the_page(): void
    {
        $company = Company::factory()->create();
        $worker = $this->makeUserWithRole($company, 'worker', []);
        $greenhouse = Greenhouse::create(['company_id' => $company->id, 'code' => 'GH-01', 'name' => 'GH A', 'status' => 'active']);
        $crop = Crop::create(['company_id' => $company->id, 'name' => 'Melon']);
        $variety = Variety::create(['company_id' => $company->id, 'crop_id' => $crop->id, 'name' => 'Golden']);
        $season = Season::create([
            'company_id' => $company->id, 'greenhouse_id' => $greenhouse->id,
            'crop_id' => $crop->id, 'variety_id' => $variety->id,
            'season_name' => 'Musim Terlarang', 'planting_date' => now()->toDateString(), 'status' => 'active',
        ]);

        Livewire::actingAs($worker)->test(Detail::class, ['season' => $season])->assertForbidden();
    }
}
