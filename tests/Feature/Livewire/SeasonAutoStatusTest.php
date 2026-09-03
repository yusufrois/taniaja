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

class SeasonAutoStatusTest extends TestCase
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

    /**
     * "Saat tanggal tanam sesuai, otomatis pindah Aktif" — the season
     * is created with a planting_date in the PAST (already due), so
     * simply loading the page (render()) should promote it.
     */
    public function test_a_planning_season_past_its_planting_date_auto_promotes_to_active(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['season.view']);
        $data = $this->baseData($company);

        $season = Season::create([
            'company_id' => $company->id, 'greenhouse_id' => $data['greenhouse']->id,
            'crop_id' => $data['crop']->id, 'variety_id' => $data['variety']->id,
            'season_name' => 'Musim Lewat', 'planting_date' => now()->subDays(3)->toDateString(),
            'status' => 'planning',
        ]);

        Livewire::actingAs($owner)->test(Manage::class);

        $this->assertEquals('active', $season->fresh()->status);
    }

    /** A season not yet due stays "planning" — no premature promotion. */
    public function test_a_planning_season_not_yet_due_stays_planning(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['season.view']);
        $data = $this->baseData($company);

        $season = Season::create([
            'company_id' => $company->id, 'greenhouse_id' => $data['greenhouse']->id,
            'crop_id' => $data['crop']->id, 'variety_id' => $data['variety']->id,
            'season_name' => 'Musim Belum Waktunya', 'planting_date' => now()->addDays(5)->toDateString(),
            'status' => 'planning',
        ]);

        Livewire::actingAs($owner)->test(Manage::class);

        $this->assertEquals('planning', $season->fresh()->status);
    }

    /**
     * "Saat user input panen pindah jadi panen" — filling in
     * actual_harvest_date on an active season auto-sets status to
     * 'harvesting', without the user touching the status dropdown.
     */
    public function test_filling_in_actual_harvest_date_auto_promotes_to_harvesting(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['season.view', 'season.update']);
        $data = $this->baseData($company);

        $season = Season::create([
            'company_id' => $company->id, 'greenhouse_id' => $data['greenhouse']->id,
            'crop_id' => $data['crop']->id, 'variety_id' => $data['variety']->id,
            'season_name' => 'Musim Aktif', 'planting_date' => now()->subDays(30)->toDateString(),
            'status' => 'active',
        ]);

        Livewire::actingAs($owner)
            ->test(Manage::class)
            ->call('openEdit', $season->id)
            ->set('actual_harvest_date', now()->toDateString())
            ->call('save')
            ->assertHasNoErrors();

        $season->refresh();
        $this->assertEquals('harvesting', $season->status);
        $this->assertEquals(now()->toDateString(), $season->actual_harvest_date->toDateString());
    }

    /**
     * "Selesai dilakukan manual" — a completed season must NEVER be
     * silently flipped back by the auto-promotion logic, even if its
     * planting_date is long past.
     */
    public function test_a_completed_season_is_never_touched_by_auto_promotion(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['season.view']);
        $data = $this->baseData($company);

        $season = Season::create([
            'company_id' => $company->id, 'greenhouse_id' => $data['greenhouse']->id,
            'crop_id' => $data['crop']->id, 'variety_id' => $data['variety']->id,
            'season_name' => 'Musim Selesai', 'planting_date' => now()->subDays(60)->toDateString(),
            'actual_harvest_date' => now()->subDays(5)->toDateString(), 'status' => 'completed',
        ]);

        Livewire::actingAs($owner)->test(Manage::class);

        $this->assertEquals('completed', $season->fresh()->status);
    }
}
