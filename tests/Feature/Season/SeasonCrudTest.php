<?php

namespace Tests\Feature\Season;

use App\Models\Company;
use App\Models\Crop;
use App\Models\Greenhouse;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Variety;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeasonCrudTest extends TestCase
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

    public function test_owner_can_create_season_with_valid_crop_variety_pair(): void
    {
        $company = Company::factory()->create();
        $greenhouse = Greenhouse::factory()->create(['company_id' => $company->id]);
        $crop = Crop::factory()->create(['company_id' => $company->id]);
        $variety = Variety::factory()->create(['company_id' => $company->id, 'crop_id' => $crop->id]);
        $owner = $this->makeUserWithRole($company, 'owner', ['season.create', 'season.view']);

        $response = $this->actingAs($owner)->postJson('/api/v1/seasons', [
            'greenhouse_id' => $greenhouse->id,
            'crop_id' => $crop->id,
            'variety_id' => $variety->id,
            'season_name' => 'Musim 1',
            'planting_date' => now()->subDays(10)->toDateString(),
            'plant_count' => 1000,
        ]);

        $response->assertCreated();
        // JsonResource wraps a single resource in {"data": {...}} by default.
        $response->assertJsonPath('data.hst', 10);
    }

    public function test_variety_not_belonging_to_selected_crop_is_rejected(): void
    {
        $company = Company::factory()->create();
        $greenhouse = Greenhouse::factory()->create(['company_id' => $company->id]);
        $cropA = Crop::factory()->create(['company_id' => $company->id]);
        $cropB = Crop::factory()->create(['company_id' => $company->id]);
        $varietyOfCropB = Variety::factory()->create(['company_id' => $company->id, 'crop_id' => $cropB->id]);
        $owner = $this->makeUserWithRole($company, 'owner', ['season.create', 'season.view']);

        $response = $this->actingAs($owner)->postJson('/api/v1/seasons', [
            'greenhouse_id' => $greenhouse->id,
            'crop_id' => $cropA->id, // mismatched with variety below
            'variety_id' => $varietyOfCropB->id,
            'season_name' => 'Musim Salah',
            'planting_date' => now()->toDateString(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('variety_id');
    }

    public function test_greenhouse_cannot_have_two_running_seasons_at_once(): void
    {
        $company = Company::factory()->create();
        $greenhouse = Greenhouse::factory()->create(['company_id' => $company->id]);
        $crop = Crop::factory()->create(['company_id' => $company->id]);
        $variety = Variety::factory()->create(['company_id' => $company->id, 'crop_id' => $crop->id]);
        $owner = $this->makeUserWithRole($company, 'owner', ['season.create', 'season.view']);

        // first season, still running (default status = planning)
        $this->actingAs($owner)->postJson('/api/v1/seasons', [
            'greenhouse_id' => $greenhouse->id,
            'crop_id' => $crop->id,
            'variety_id' => $variety->id,
            'season_name' => 'Musim 1',
            'planting_date' => now()->toDateString(),
        ])->assertCreated();

        // second season on the SAME greenhouse while the first is still running
        $response = $this->actingAs($owner)->postJson('/api/v1/seasons', [
            'greenhouse_id' => $greenhouse->id,
            'crop_id' => $crop->id,
            'variety_id' => $variety->id,
            'season_name' => 'Musim 2',
            'planting_date' => now()->toDateString(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('greenhouse_id');
    }

    public function test_supervisor_can_create_but_not_update_season(): void
    {
        $company = Company::factory()->create();
        $greenhouse = Greenhouse::factory()->create(['company_id' => $company->id]);
        $crop = Crop::factory()->create(['company_id' => $company->id]);
        $variety = Variety::factory()->create(['company_id' => $company->id, 'crop_id' => $crop->id]);
        $supervisor = $this->makeUserWithRole($company, 'supervisor', ['season.create', 'season.view']);

        $create = $this->actingAs($supervisor)->postJson('/api/v1/seasons', [
            'greenhouse_id' => $greenhouse->id,
            'crop_id' => $crop->id,
            'variety_id' => $variety->id,
            'season_name' => 'Musim Supervisor',
            'planting_date' => now()->toDateString(),
        ]);
        $create->assertCreated();

        $seasonId = $create->json('data.id');

        $update = $this->actingAs($supervisor)->putJson("/api/v1/seasons/{$seasonId}", [
            'season_name' => 'Musim Diubah',
        ]);
        $update->assertForbidden(); // supervisor lacks season.update per matrix
    }
}
