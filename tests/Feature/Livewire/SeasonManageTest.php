<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Season\Manage;
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

class SeasonManageTest extends TestCase
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

    public function test_owner_can_create_a_season(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['season.view', 'season.create']);
        $data = $this->baseData($company);

        Livewire::actingAs($owner)
            ->test(Manage::class)
            ->call('openCreate')
            ->set('greenhouse_id', $data['greenhouse']->id)
            ->set('crop_id', $data['crop']->id)
            ->set('variety_id', $data['variety']->id)
            ->set('season_name', 'Musim 1')
            ->set('planting_date', '2026-08-01')
            ->set('status', 'planning')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('seasons', ['company_id' => $company->id, 'season_name' => 'Musim 1']);
    }

    /**
     * Variety must belong to the selected crop — same cross-field rule
     * StoreSeasonRequest enforces on the API.
     */
    public function test_variety_not_belonging_to_chosen_crop_is_rejected(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['season.view', 'season.create']);
        $data = $this->baseData($company);
        $otherCrop = Crop::create(['company_id' => $company->id, 'name' => 'Tomat']);
        $mismatchedVariety = Variety::create(['company_id' => $company->id, 'crop_id' => $otherCrop->id, 'name' => 'Cherry']);

        Livewire::actingAs($owner)
            ->test(Manage::class)
            ->call('openCreate')
            ->set('greenhouse_id', $data['greenhouse']->id)
            ->set('crop_id', $data['crop']->id)
            ->set('variety_id', $mismatchedVariety->id)
            ->set('season_name', 'Musim Salah', )
            ->set('planting_date', '2026-08-01')
            ->set('status', 'planning')
            ->call('save')
            ->assertHasErrors(['variety_id']);
    }

    /**
     * A greenhouse can't have two concurrently-running seasons — same
     * rule as the API.
     */
    public function test_cannot_start_a_second_running_season_in_the_same_greenhouse(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['season.view', 'season.create']);
        $data = $this->baseData($company);

        Season::create([
            'company_id' => $company->id, 'greenhouse_id' => $data['greenhouse']->id,
            'crop_id' => $data['crop']->id, 'variety_id' => $data['variety']->id,
            'season_name' => 'Musim Berjalan', 'planting_date' => '2026-07-01', 'status' => 'active',
        ]);

        Livewire::actingAs($owner)
            ->test(Manage::class)
            ->call('openCreate')
            ->set('greenhouse_id', $data['greenhouse']->id)
            ->set('crop_id', $data['crop']->id)
            ->set('variety_id', $data['variety']->id)
            ->set('season_name', 'Musim Kedua')
            ->set('planting_date', '2026-08-01')
            ->set('status', 'planning')
            ->call('save')
            ->assertHasErrors(['greenhouse_id']);
    }

    public function test_owner_can_edit_a_season(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['season.view', 'season.update']);
        $data = $this->baseData($company);
        $season = Season::create([
            'company_id' => $company->id, 'greenhouse_id' => $data['greenhouse']->id,
            'crop_id' => $data['crop']->id, 'variety_id' => $data['variety']->id,
            'season_name' => 'Musim Lama', 'planting_date' => '2026-07-01', 'status' => 'planning',
        ]);

        Livewire::actingAs($owner)
            ->test(Manage::class)
            ->call('openEdit', $season->id)
            ->set('season_name', 'Musim Diperbarui')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals('Musim Diperbarui', $season->fresh()->season_name);
    }

    public function test_worker_without_permission_cannot_view_seasons(): void
    {
        $company = Company::factory()->create();
        $worker = $this->makeUserWithRole($company, 'worker', []);

        $this->assertTrue($worker->cannot('viewAny', Season::class));
    }
}
