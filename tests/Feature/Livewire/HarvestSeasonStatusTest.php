<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Harvest\Manage;
use App\Models\Company;
use App\Models\Crop;
use App\Models\Grade;
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
 * Roadmap tambahan — "status Musim Tanam harus ikut berubah jadi
 * Panen begitu Panen dicatat, tapi TIDAK PERNAH otomatis jadi
 * Selesai" (perbaikan yang diminta pengguna setelah membangun
 * halaman Panen).
 */
class HarvestSeasonStatusTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithRole(Company $company, string $slug, array $permissionNames = []): User
    {
        $role = Role::firstOrCreate(
            ['company_id' => $company->id, 'slug' => $slug],
            ['name' => ucfirst($slug)]
        );

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

    private function makeSeason(Company $company, array $overrides = []): Season
    {
        $code = 'GH-'.\Illuminate\Support\Str::random(6);
        $greenhouse = Greenhouse::create(['company_id' => $company->id, 'code' => $code, 'name' => 'GH '.$code, 'status' => 'active']);
        $crop = Crop::firstOrCreate(['company_id' => $company->id, 'name' => 'Cabe']);
        $variety = Variety::firstOrCreate(['company_id' => $company->id, 'crop_id' => $crop->id, 'name' => 'Keriting']);

        return Season::create(array_merge([
            'company_id' => $company->id, 'greenhouse_id' => $greenhouse->id,
            'crop_id' => $crop->id, 'variety_id' => $variety->id,
            'season_name' => 'Musim Cabe', 'planting_date' => now()->subDays(60)->toDateString(), 'status' => 'active',
        ], $overrides));
    }

    private function recordHarvest(User $user, Season $season, Grade $grade, string $date): void
    {
        Livewire::actingAs($user)
            ->test(Manage::class)
            ->call('openCreate')
            ->set('season_id', $season->id)
            ->set('harvest_date', $date)
            ->set('items.0.grade_id', $grade->id)
            ->set('items.0.weight', 10)
            ->call('save');
    }

    public function test_first_harvest_promotes_season_to_harvesting(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['harvest.view', 'harvest.create']);
        $season = $this->makeSeason($company); // starts 'active'
        $grade = Grade::create(['company_id' => $company->id, 'name' => 'Grade A']);

        $this->recordHarvest($owner, $season, $grade, now()->toDateString());

        $season->refresh();
        $this->assertEquals('harvesting', $season->status);
        $this->assertEquals(now()->toDateString(), $season->actual_harvest_date->toDateString());
    }

    /** "Cabe ronde 2, 3, dst" — repeat harvests don't push the FIRST harvest date forward again. */
    public function test_subsequent_harvest_rounds_do_not_change_the_original_actual_harvest_date(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['harvest.view', 'harvest.create']);
        $season = $this->makeSeason($company);
        $grade = Grade::create(['company_id' => $company->id, 'name' => 'Grade A']);

        $this->recordHarvest($owner, $season, $grade, '2026-08-01');
        $this->recordHarvest($owner, $season, $grade, '2026-08-15'); // round 2, a week later

        $season->refresh();
        $this->assertEquals('2026-08-01', $season->actual_harvest_date->toDateString());
        $this->assertEquals('harvesting', $season->status);
        $this->assertDatabaseCount('harvests', 2);
    }

    /** A manually completed season is never dragged back to 'harvesting' by a later harvest entry. */
    public function test_recording_a_harvest_does_not_reopen_a_completed_season(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['harvest.view', 'harvest.create']);
        $season = $this->makeSeason($company, ['status' => 'completed', 'actual_harvest_date' => '2026-07-01']);
        $grade = Grade::create(['company_id' => $company->id, 'name' => 'Grade A']);

        $this->recordHarvest($owner, $season, $grade, now()->toDateString());

        $this->assertEquals('completed', $season->fresh()->status);
    }

    /**
     * Bug report: after applying the first version of this fix,
     * status still stayed "Aktif" in real usage. Root cause: this
     * Season already had a STALE (or future-dated, from an earlier
     * unrelated Season-form edit) actual_harvest_date sitting in the
     * database — the trigger check was using THAT instead of the
     * harvest actually being recorded right now.
     */
    public function test_recording_a_harvest_still_promotes_status_even_if_season_already_has_a_stale_future_actual_harvest_date(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['harvest.view', 'harvest.create']);
        // Simulates a Season that was edited earlier via the Season
        // form with a future-dated "Tanggal Panen Aktual" that never
        // actually happened yet — a real harvest is now being
        // recorded TODAY regardless of that stale field.
        $season = $this->makeSeason($company, ['actual_harvest_date' => now()->addDays(30)->toDateString()]);
        $grade = Grade::create(['company_id' => $company->id, 'name' => 'Grade A']);

        $this->recordHarvest($owner, $season, $grade, now()->toDateString());

        $this->assertEquals('harvesting', $season->fresh()->status);
    }
}
