<?php

namespace Tests\Feature\Cultivation;

use App\Models\Company;
use App\Models\Crop;
use App\Models\Greenhouse;
use App\Models\Permission;
use App\Models\PlantLoss;
use App\Models\Role;
use App\Models\Season;
use App\Models\User;
use App\Models\Variety;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlantLossTest extends TestCase
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

    private function makeSeason(Company $company, int $plantCount = 1000): Season
    {
        $greenhouse = Greenhouse::factory()->create(['company_id' => $company->id]);
        $crop = Crop::factory()->create(['company_id' => $company->id]);
        $variety = Variety::factory()->create(['company_id' => $company->id, 'crop_id' => $crop->id]);

        return Season::create([
            'company_id' => $company->id,
            'greenhouse_id' => $greenhouse->id,
            'crop_id' => $crop->id,
            'variety_id' => $variety->id,
            'season_name' => 'Musim Plant Loss Test',
            'planting_date' => now()->subDays(20),
            'plant_count' => $plantCount,
            'status' => 'active',
        ]);
    }

    public function test_recording_plant_loss_reduces_current_plant_count(): void
    {
        $company = Company::factory()->create();
        $season = $this->makeSeason($company, 1000);
        $worker = $this->makeUserWithRole($company, 'worker', ['plant_loss.create', 'plant_loss.view']);

        $response = $this->actingAs($worker)->postJson('/api/v1/plant-losses', [
            'season_id' => $season->id,
            'date' => now()->toDateString(),
            'quantity' => 50,
            'cause' => 'Penyakit layu fusarium',
        ]);

        $response->assertCreated();

        $season->refresh();
        $this->assertEquals(950, $season->current_plant_count);
        $this->assertEquals(95.0, $season->survival_rate_percent);
    }

    public function test_multiple_plant_losses_accumulate(): void
    {
        $company = Company::factory()->create();
        $season = $this->makeSeason($company, 1000);
        $owner = $this->makeUserWithRole($company, 'owner', ['plant_loss.create']);

        $this->actingAs($owner)->postJson('/api/v1/plant-losses', [
            'season_id' => $season->id,
            'date' => now()->toDateString(),
            'quantity' => 30,
        ])->assertCreated();

        $this->actingAs($owner)->postJson('/api/v1/plant-losses', [
            'season_id' => $season->id,
            'date' => now()->toDateString(),
            'quantity' => 20,
        ])->assertCreated();

        $this->assertEquals(950, $season->fresh()->current_plant_count);
    }

    public function test_cannot_record_more_deaths_than_plants_currently_alive(): void
    {
        $company = Company::factory()->create();
        $season = $this->makeSeason($company, 100);
        $owner = $this->makeUserWithRole($company, 'owner', ['plant_loss.create']);

        // First loss: 80 out of 100 — still valid (20 remain alive).
        $this->actingAs($owner)->postJson('/api/v1/plant-losses', [
            'season_id' => $season->id,
            'date' => now()->toDateString(),
            'quantity' => 80,
        ])->assertCreated();

        // Second loss: 21 more — exceeds the 20 still alive, must be rejected.
        $response = $this->actingAs($owner)->postJson('/api/v1/plant-losses', [
            'season_id' => $season->id,
            'date' => now()->toDateString(),
            'quantity' => 21,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('quantity');
        // Confirm the rejected request truly did not get persisted.
        $this->assertEquals(20, $season->fresh()->current_plant_count);
    }

    public function test_finance_role_cannot_record_plant_loss(): void
    {
        $company = Company::factory()->create();
        $season = $this->makeSeason($company, 500);
        $finance = $this->makeUserWithRole($company, 'finance', []); // finance has no plant_loss permission per matrix

        $response = $this->actingAs($finance)->postJson('/api/v1/plant-losses', [
            'season_id' => $season->id,
            'date' => now()->toDateString(),
            'quantity' => 10,
        ]);

        $response->assertForbidden();
    }

    public function test_deleting_a_plant_loss_restores_current_plant_count(): void
    {
        $company = Company::factory()->create();
        $season = $this->makeSeason($company, 1000);
        $owner = $this->makeUserWithRole($company, 'owner', ['plant_loss.create', 'plant_loss.delete']);

        $create = $this->actingAs($owner)->postJson('/api/v1/plant-losses', [
            'season_id' => $season->id,
            'date' => now()->toDateString(),
            'quantity' => 40,
        ]);
        $lossId = $create->json('data.id');
        $this->assertEquals(960, $season->fresh()->current_plant_count);

        $this->actingAs($owner)->deleteJson("/api/v1/plant-losses/{$lossId}")->assertOk();

        $this->assertEquals(1000, $season->fresh()->current_plant_count);
    }

    public function test_season_dashboard_reflects_plant_loss(): void
    {
        $company = Company::factory()->create();
        $season = $this->makeSeason($company, 1000);
        $owner = $this->makeUserWithRole($company, 'owner', ['plant_loss.create', 'season.view']);

        $this->actingAs($owner)->postJson('/api/v1/plant-losses', [
            'season_id' => $season->id,
            'date' => now()->toDateString(),
            'quantity' => 100,
        ])->assertCreated();

        $dashboard = $this->actingAs($owner)->getJson("/api/v1/seasons/{$season->id}/dashboard");
        $dashboard->assertOk();

        $this->assertEquals(900, $dashboard->json('summary.current_plant_count'));
        $this->assertEquals(90.0, $dashboard->json('summary.survival_rate_percent'));
    }
}
