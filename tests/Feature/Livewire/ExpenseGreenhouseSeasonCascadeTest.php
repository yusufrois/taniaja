<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Expense\Manage;
use App\Models\Company;
use App\Models\Crop;
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
 * Roadmap tambahan (permintaan pengguna) — "musim tanam yang muncul
 * cuma yang ada di greenhouse itu, biar gak rancu".
 */
class ExpenseGreenhouseSeasonCascadeTest extends TestCase
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

    public function test_selecting_a_greenhouse_filters_seasons_to_that_greenhouse_only(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['expense.view']);
        $crop = Crop::factory()->create(['company_id' => $company->id]);
        $variety = Variety::factory()->create(['company_id' => $company->id, 'crop_id' => $crop->id]);

        $greenhouseA = Greenhouse::create(['company_id' => $company->id, 'code' => 'GH-A', 'name' => 'GH A', 'status' => 'active']);
        $greenhouseB = Greenhouse::create(['company_id' => $company->id, 'code' => 'GH-B', 'name' => 'GH B', 'status' => 'active']);

        $seasonA = Season::create([
            'company_id' => $company->id, 'greenhouse_id' => $greenhouseA->id,
            'crop_id' => $crop->id, 'variety_id' => $variety->id,
            'season_name' => 'Musim di GH-A', 'planting_date' => now()->subDays(10), 'status' => 'active',
        ]);
        $seasonB = Season::create([
            'company_id' => $company->id, 'greenhouse_id' => $greenhouseB->id,
            'crop_id' => $crop->id, 'variety_id' => $variety->id,
            'season_name' => 'Musim di GH-B', 'planting_date' => now()->subDays(10), 'status' => 'active',
        ]);

        // Before picking a greenhouse: both seasons show.
        $component = Livewire::actingAs($owner)->test(Manage::class);
        $this->assertCount(2, $component->viewData('seasons'));

        // After picking GH-A: only GH-A's season shows.
        $component->set('greenhouse_id', $greenhouseA->id);
        $seasons = $component->viewData('seasons');
        $this->assertCount(1, $seasons);
        $this->assertEquals($seasonA->id, $seasons->first()->id);
    }

    /** Switching to a DIFFERENT greenhouse clears a season choice that no longer belongs to it. */
    public function test_switching_greenhouse_resets_the_previously_chosen_season(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['expense.view']);
        $crop = Crop::factory()->create(['company_id' => $company->id]);
        $variety = Variety::factory()->create(['company_id' => $company->id, 'crop_id' => $crop->id]);

        $greenhouseA = Greenhouse::create(['company_id' => $company->id, 'code' => 'GH-A', 'name' => 'GH A', 'status' => 'active']);
        $greenhouseB = Greenhouse::create(['company_id' => $company->id, 'code' => 'GH-B', 'name' => 'GH B', 'status' => 'active']);
        $seasonA = Season::create([
            'company_id' => $company->id, 'greenhouse_id' => $greenhouseA->id,
            'crop_id' => $crop->id, 'variety_id' => $variety->id,
            'season_name' => 'Musim di GH-A', 'planting_date' => now()->subDays(10), 'status' => 'active',
        ]);

        Livewire::actingAs($owner)
            ->test(Manage::class)
            ->set('greenhouse_id', $greenhouseA->id)
            ->set('season_id', $seasonA->id)
            ->set('greenhouse_id', $greenhouseB->id) // switch away
            ->assertSet('season_id', null);
    }
}
