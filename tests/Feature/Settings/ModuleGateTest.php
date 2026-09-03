<?php

namespace Tests\Feature\Settings;

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
 * Roadmap tambahan — menutup celah "toggle modul cuma sembunyikan
 * link sidebar, halamannya masih bisa dibuka langsung lewat URL".
 */
class ModuleGateTest extends TestCase
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

    public function test_greenhouse_page_is_blocked_directly_by_url_when_budidaya_is_disabled(): void
    {
        $company = Company::factory()->create(['enabled_modules' => ['budidaya' => false]]);
        $owner = $this->makeUserWithRole($company, 'owner', ['greenhouse.view']);

        $this->actingAs($owner)->get('/greenhouses')->assertNotFound();
    }

    public function test_season_page_is_blocked_directly_by_url_when_budidaya_is_disabled(): void
    {
        $company = Company::factory()->create(['enabled_modules' => ['budidaya' => false]]);
        $owner = $this->makeUserWithRole($company, 'owner', ['season.view']);

        $this->actingAs($owner)->get('/seasons')->assertNotFound();
    }

    public function test_season_detail_page_is_blocked_directly_by_url_when_budidaya_is_disabled(): void
    {
        $company = Company::factory()->create(['enabled_modules' => ['budidaya' => false]]);
        $owner = $this->makeUserWithRole($company, 'owner', ['season.view']);
        $greenhouse = Greenhouse::create(['company_id' => $company->id, 'code' => 'GH-01', 'name' => 'GH A', 'status' => 'active']);
        $crop = Crop::create(['company_id' => $company->id, 'name' => 'Melon']);
        $variety = Variety::create(['company_id' => $company->id, 'crop_id' => $crop->id, 'name' => 'Golden']);
        $season = Season::create([
            'company_id' => $company->id, 'greenhouse_id' => $greenhouse->id,
            'crop_id' => $crop->id, 'variety_id' => $variety->id,
            'season_name' => 'Musim Tersembunyi', 'planting_date' => now()->toDateString(), 'status' => 'active',
        ]);

        $this->actingAs($owner)->get("/seasons/{$season->id}")->assertNotFound();
    }

    /** Confirm the pages still work normally when the module IS enabled (the default) — no regression. */
    public function test_greenhouse_and_season_pages_still_work_when_budidaya_is_enabled(): void
    {
        $company = Company::factory()->create(); // default: enabled
        $owner = $this->makeUserWithRole($company, 'owner', ['greenhouse.view', 'season.view']);

        $this->actingAs($owner)->get('/greenhouses')->assertOk();
        $this->actingAs($owner)->get('/seasons')->assertOk();
    }
}
