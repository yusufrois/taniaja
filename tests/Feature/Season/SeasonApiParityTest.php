<?php

namespace Tests\Feature\Season;

use App\Models\Company;
use App\Models\Crop;
use App\Models\Greenhouse;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Season;
use App\Models\User;
use App\Models\Variety;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Roadmap tambahan "Phase 10 — finalisasi API/Flutter readiness":
 * bug #8's fix (auto-status) previously only worked through the web
 * Livewire page — a Flutter app calling the API directly got none of
 * it. This proves the SAME behavior now works through the raw API,
 * via the shared SeasonStatusService.
 */
class SeasonApiParityTest extends TestCase
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

    private function baseData(Company $company): array
    {
        return [
            'greenhouse' => Greenhouse::create(['company_id' => $company->id, 'code' => 'GH-01', 'name' => 'GH A', 'status' => 'active']),
            'crop' => $crop = Crop::create(['company_id' => $company->id, 'name' => 'Melon']),
            'variety' => Variety::create(['company_id' => $company->id, 'crop_id' => $crop->id, 'name' => 'Honey Globe']),
        ];
    }

    public function test_api_store_does_not_prematurely_flip_status_for_a_future_actual_harvest_date(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['season.create']);
        $data = $this->baseData($company);

        $response = $this->actingAs($owner)->postJson('/api/v1/seasons', [
            'greenhouse_id' => $data['greenhouse']->id,
            'crop_id' => $data['crop']->id,
            'variety_id' => $data['variety']->id,
            'season_name' => 'Musim API',
            'planting_date' => now()->toDateString(),
            'actual_harvest_date' => now()->addMonths(2)->toDateString(), // future
            'status' => 'planning',
        ]);

        $response->assertCreated();
        $this->assertEquals('planning', $response->json('data.status'));
    }

    public function test_api_store_correctly_flips_to_harvesting_for_a_past_actual_harvest_date(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['season.create']);
        $data = $this->baseData($company);

        $response = $this->actingAs($owner)->postJson('/api/v1/seasons', [
            'greenhouse_id' => $data['greenhouse']->id,
            'crop_id' => $data['crop']->id,
            'variety_id' => $data['variety']->id,
            'season_name' => 'Musim API Panen',
            'planting_date' => now()->subDays(60)->toDateString(),
            'actual_harvest_date' => now()->subDays(1)->toDateString(),
            'status' => 'active',
        ]);

        $response->assertCreated();
        $this->assertEquals('harvesting', $response->json('data.status'));
    }

    public function test_api_rejects_estimated_harvest_date_before_planting_date(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['season.create']);
        $data = $this->baseData($company);

        $response = $this->actingAs($owner)->postJson('/api/v1/seasons', [
            'greenhouse_id' => $data['greenhouse']->id,
            'crop_id' => $data['crop']->id,
            'variety_id' => $data['variety']->id,
            'season_name' => 'Musim Salah',
            'planting_date' => now()->toDateString(),
            'estimated_harvest_date' => now()->subDays(10)->toDateString(),
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['estimated_harvest_date']);
    }

    public function test_api_update_rejects_actual_harvest_date_before_existing_planting_date_even_on_partial_update(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['season.view', 'season.update']);
        $data = $this->baseData($company);

        $season = Season::create([
            'company_id' => $company->id, 'greenhouse_id' => $data['greenhouse']->id,
            'crop_id' => $data['crop']->id, 'variety_id' => $data['variety']->id,
            'season_name' => 'Musim Existing', 'planting_date' => now()->toDateString(), 'status' => 'active',
        ]);

        // Partial update — does NOT send planting_date at all.
        $response = $this->actingAs($owner)->putJson("/api/v1/seasons/{$season->id}", [
            'actual_harvest_date' => now()->subDays(30)->toDateString(), // before planting_date
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['actual_harvest_date']);
    }

    public function test_api_index_promotes_due_seasons_from_planning_to_active(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['season.view']);
        $data = $this->baseData($company);

        $season = Season::create([
            'company_id' => $company->id, 'greenhouse_id' => $data['greenhouse']->id,
            'crop_id' => $data['crop']->id, 'variety_id' => $data['variety']->id,
            'season_name' => 'Musim Lewat Waktu', 'planting_date' => now()->subDays(3)->toDateString(),
            'status' => 'planning',
        ]);

        $this->actingAs($owner)->getJson('/api/v1/seasons')->assertOk();

        $this->assertEquals('active', $season->fresh()->status);
    }
}
