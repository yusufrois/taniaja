<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Expense\Manage;
use App\Models\Company;
use App\Models\Crop;
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
 * Roadmap tambahan — "selain greenhouse harus ada musim berapa" di
 * tabel Beban, karena 1 greenhouse bisa punya banyak musim, jadi kode
 * GH saja tidak cukup untuk membedakan.
 */
class ExpenseSeasonColumnTest extends TestCase
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

    public function test_expense_list_shows_which_season_each_expense_belongs_to(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['expense.view']);
        $greenhouse = Greenhouse::create(['company_id' => $company->id, 'code' => 'GH-A', 'name' => 'GH A', 'status' => 'active']);
        $crop = Crop::create(['company_id' => $company->id, 'name' => 'Melon']);
        $variety = Variety::create(['company_id' => $company->id, 'crop_id' => $crop->id, 'name' => 'Golden']);
        $seasonB = Season::create([
            'company_id' => $company->id, 'greenhouse_id' => $greenhouse->id,
            'crop_id' => $crop->id, 'variety_id' => $variety->id,
            'season_name' => 'Musim 2 - GH A', 'planting_date' => now()->toDateString(), 'status' => 'active',
        ]);
        $category = ExpenseCategory::create(['company_id' => $company->id, 'name' => 'Pupuk']);
        Expense::create([
            'company_id' => $company->id, 'greenhouse_id' => $greenhouse->id, 'season_id' => $seasonB->id,
            'expense_category_id' => $category->id, 'date' => now()->toDateString(), 'amount' => 100000,
        ]);

        Livewire::actingAs($owner)->test(Manage::class)->assertSee('Musim 2 - GH A');
    }
}
