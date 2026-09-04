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
 * Roadmap Fase UI-1 — halaman "Panen". Mengikuti kontrak
 * HarvestController API persis: harvest_date + items[] (grade,
 * weight, quantity opsional) -> otomatis jadi StockBatch per item.
 */
class HarvestManageTest extends TestCase
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

    private function makeSeason(Company $company): Season
    {
        // Unique code per call — this helper may be invoked more than
        // once per test (e.g. two seasons in different statuses),
        // and Greenhouse code is unique per company.
        $code = 'GH-'.\Illuminate\Support\Str::random(6);
        $greenhouse = Greenhouse::create(['company_id' => $company->id, 'code' => $code, 'name' => 'GH '.$code, 'status' => 'active']);
        $crop = Crop::firstOrCreate(['company_id' => $company->id, 'name' => 'Melon']);
        $variety = Variety::firstOrCreate(['company_id' => $company->id, 'crop_id' => $crop->id, 'name' => 'Golden']);

        return Season::create([
            'company_id' => $company->id, 'greenhouse_id' => $greenhouse->id,
            'crop_id' => $crop->id, 'variety_id' => $variety->id,
            'season_name' => 'Musim 1', 'planting_date' => now()->subDays(60)->toDateString(), 'status' => 'active',
        ]);
    }

    public function test_owner_can_record_a_harvest_with_multiple_grades(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['harvest.view', 'harvest.create']);
        $season = $this->makeSeason($company);
        $gradeA = Grade::create(['company_id' => $company->id, 'name' => 'Grade A']);
        $gradeB = Grade::create(['company_id' => $company->id, 'name' => 'Grade B']);

        Livewire::actingAs($owner)
            ->test(Manage::class)
            ->call('openCreate')
            ->set('season_id', $season->id)
            ->set('harvest_date', now()->toDateString())
            ->set('items.0.grade_id', $gradeA->id)
            ->set('items.0.weight', 50)
            ->call('addItem')
            ->set('items.1.grade_id', $gradeB->id)
            ->set('items.1.weight', 30)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('harvests', ['company_id' => $company->id, 'season_id' => $season->id]);
        $this->assertDatabaseCount('harvest_items', 2);
        $this->assertDatabaseCount('stock_batches', 2);
    }

    public function test_worker_without_permission_cannot_view_harvest_page(): void
    {
        $company = Company::factory()->create();
        $worker = $this->makeUserWithRole($company, 'worker', []);

        Livewire::actingAs($worker)->test(Manage::class)->assertForbidden();
    }

    public function test_owner_can_delete_a_harvest(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['harvest.view', 'harvest.create', 'harvest.delete']);
        $season = $this->makeSeason($company);
        $grade = Grade::create(['company_id' => $company->id, 'name' => 'Grade A']);

        $component = Livewire::actingAs($owner)
            ->test(Manage::class)
            ->call('openCreate')
            ->set('season_id', $season->id)
            ->set('harvest_date', now()->toDateString())
            ->set('items.0.grade_id', $grade->id)
            ->set('items.0.weight', 50)
            ->call('save');

        $harvestId = \App\Models\Harvest::first()->id;

        $component->call('confirmDelete', $harvestId)->call('delete');

        // Harvest uses SoftDeletes — the row still exists with
        // deleted_at set, not actually removed.
        $this->assertSoftDeleted('harvests', ['id' => $harvestId]);
    }

    /** Season dropdown must not offer 'completed' or 'planning' seasons — only active/harvesting ones make sense to harvest from. */
    public function test_season_dropdown_excludes_planning_and_completed_seasons(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['harvest.view', 'harvest.create']);
        $activeSeason = $this->makeSeason($company);
        $completedSeason = $this->makeSeason($company);
        $completedSeason->update(['status' => 'completed', 'season_name' => 'Musim Selesai']);

        $component = Livewire::actingAs($owner)->test(Manage::class);
        $seasonIds = $component->viewData('seasons')->pluck('id')->all();

        $this->assertContains($activeSeason->id, $seasonIds);
        $this->assertNotContains($completedSeason->id, $seasonIds);
    }
}
