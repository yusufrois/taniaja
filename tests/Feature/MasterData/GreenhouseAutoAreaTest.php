<?php

namespace Tests\Feature\MasterData;

use App\Livewire\Greenhouse\Manage;
use App\Models\Company;
use App\Models\Greenhouse;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Roadmap tambahan #5 — "luas harusnya otomatis terhitung dari
 * perkalian panjang x lebar tanpa isi manual".
 */
class GreenhouseAutoAreaTest extends TestCase
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

    public function test_area_is_automatically_computed_from_length_times_width_at_model_level(): void
    {
        $company = Company::factory()->create();

        $greenhouse = Greenhouse::create([
            'company_id' => $company->id, 'code' => 'GH-01', 'name' => 'GH A',
            'length' => 10, 'width' => 8, 'status' => 'active',
        ]);

        $this->assertEquals(80.0, (float) $greenhouse->area);
    }

    /** Even if 'area' is explicitly sent, the computed value wins — no manual override possible. */
    public function test_manually_supplied_area_is_overridden_by_the_computed_value(): void
    {
        $company = Company::factory()->create();

        $greenhouse = Greenhouse::create([
            'company_id' => $company->id, 'code' => 'GH-02', 'name' => 'GH B',
            'length' => 5, 'width' => 4, 'area' => 999, 'status' => 'active', // area deliberately wrong
        ]);

        $this->assertEquals(20.0, (float) $greenhouse->area); // 5*4, NOT 999
    }

    public function test_updating_width_recomputes_area(): void
    {
        $company = Company::factory()->create();
        $greenhouse = Greenhouse::create([
            'company_id' => $company->id, 'code' => 'GH-03', 'name' => 'GH C',
            'length' => 10, 'width' => 5, 'status' => 'active',
        ]);
        $this->assertEquals(50.0, (float) $greenhouse->area);

        $greenhouse->update(['width' => 6]);

        $this->assertEquals(60.0, (float) $greenhouse->fresh()->area);
    }

    /** Missing one dimension leaves area untouched (doesn't blank out existing data). */
    public function test_area_is_left_untouched_when_only_one_dimension_is_present(): void
    {
        $company = Company::factory()->create();

        $greenhouse = Greenhouse::create([
            'company_id' => $company->id, 'code' => 'GH-04', 'name' => 'GH D',
            'length' => 10, 'area' => 500, 'status' => 'active', // width missing
        ]);

        $this->assertEquals(500.0, (float) $greenhouse->area); // untouched, not recomputed to null
    }

    public function test_livewire_form_shows_a_live_computed_area_preview(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['greenhouse.view', 'greenhouse.create']);

        Livewire::actingAs($owner)
            ->test(Manage::class)
            ->call('openCreate')
            ->set('length', 12)
            ->set('width', 7)
            ->assertSet('area', 84.0);
    }

    public function test_api_ignores_a_manually_sent_area_value(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['greenhouse.create']);

        $response = $this->actingAs($owner)->postJson('/api/v1/greenhouses', [
            'code' => 'GH-API', 'name' => 'GH API Test',
            'length' => 6, 'width' => 3, 'area' => 12345, // deliberately wrong
        ]);

        $response->assertCreated();
        $this->assertEquals(18.0, $response->json('data.area')); // 6*3, NOT 12345
    }
}
