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

/**
 * Bug report #8 — a FUTURE-dated actual_harvest_date (e.g. estimasi
 * panen 04 November 2026, diisi juga di tanggal panen aktual, padahal
 * hari ini baru 02 September 2026) was wrongly flipping status to
 * 'harvesting' immediately, even though the harvest hasn't happened
 * yet. Also covers the new date-order validation.
 */
class SeasonHarvestDateBugTest extends TestCase
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

    public function test_a_future_dated_actual_harvest_date_does_not_prematurely_flip_status_to_harvesting(): void
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
            ->set('season_name', 'Musim Test')
            // planting_date set in the FUTURE too — isolates this test
            // to ONLY the harvest-trigger behavior, without the
            // separate "auto-promote to active once planting_date
            // arrives" feature also kicking in and changing status.
            ->set('planting_date', now()->addDays(5)->toDateString())
            ->set('estimated_harvest_date', now()->addMonths(2)->toDateString())
            ->set('actual_harvest_date', now()->addMonths(2)->toDateString()) // FUTURE date
            ->set('status', 'planning')
            ->call('save')
            ->assertHasNoErrors();

        $season = Season::where('season_name', 'Musim Test')->first();
        $this->assertEquals('planning', $season->status); // NOT prematurely 'harvesting'
    }

    /** A PAST/today actual_harvest_date still correctly triggers 'harvesting' — the original feature still works. */
    public function test_a_past_actual_harvest_date_still_correctly_triggers_harvesting(): void
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
            ->set('season_name', 'Musim Panen Beneran')
            ->set('planting_date', now()->subDays(60)->toDateString())
            ->set('actual_harvest_date', now()->subDays(1)->toDateString()) // yesterday — already happened
            ->set('status', 'active')
            ->call('save')
            ->assertHasNoErrors();

        $season = Season::where('season_name', 'Musim Panen Beneran')->first();
        $this->assertEquals('harvesting', $season->status);
    }

    /** New validation: harvest dates cannot be before the planting date. */
    public function test_estimated_harvest_date_before_planting_date_is_rejected(): void
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
            ->set('season_name', 'Musim Salah Tanggal')
            ->set('planting_date', now()->toDateString())
            ->set('estimated_harvest_date', now()->subDays(10)->toDateString()) // BEFORE planting
            ->call('save')
            ->assertHasErrors(['estimated_harvest_date']);
    }
}
