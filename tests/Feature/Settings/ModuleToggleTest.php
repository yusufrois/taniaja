<?php

namespace Tests\Feature\Settings;

use App\Livewire\Settings\ModuleToggle;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Roadmap tambahan — "toggle per modul" (disepakati dengan pengguna
 * sebagai jalan tengah antara "gabung semua" dan "pisah total tipe
 * usaha petani/tengkulak saat daftar").
 */
class ModuleToggleTest extends TestCase
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

    public function test_a_company_with_no_settings_configured_has_every_module_enabled_by_default(): void
    {
        $company = Company::factory()->create(); // enabled_modules never touched

        $this->assertTrue($company->hasModuleEnabled('budidaya'));
    }

    public function test_owner_can_turn_off_the_budidaya_module(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['company.update']);

        Livewire::actingAs($owner)
            ->test(ModuleToggle::class)
            ->set('enabled.budidaya', false)
            ->call('save');

        $this->assertFalse($company->fresh()->hasModuleEnabled('budidaya'));
    }

    /** Turning the module off then back on works — nothing is permanently locked. */
    public function test_module_can_be_re_enabled_after_being_turned_off(): void
    {
        $company = Company::factory()->create(['enabled_modules' => ['budidaya' => false]]);
        $owner = $this->makeUserWithRole($company, 'owner', ['company.update']);

        Livewire::actingAs($owner)
            ->test(ModuleToggle::class)
            ->assertSet('enabled.budidaya', false)
            ->set('enabled.budidaya', true)
            ->call('save');

        $this->assertTrue($company->fresh()->hasModuleEnabled('budidaya'));
    }

    public function test_sidebar_hides_greenhouse_and_season_links_when_budidaya_is_disabled(): void
    {
        $company = Company::factory()->create(['enabled_modules' => ['budidaya' => false]]);
        $owner = $this->makeUserWithRole($company, 'owner', ['report.view', 'activity.view']);

        $response = $this->actingAs($owner)->get('/dashboard');

        // Checking the exact sidebar link markup (icon+label), not a
        // bare "Greenhouse" substring — that also matches unrelated
        // text elsewhere on the dashboard (the "Greenhouse Aktif"
        // stat widget, and "activeGreenhouses" inside Livewire's
        // embedded JSON snapshot), which isn't what this test is
        // actually about.
        $response->assertDontSee('>Greenhouse</span>', false)
            ->assertDontSee('>Musim Tanam</span>', false);
    }

    public function test_sidebar_shows_greenhouse_and_season_links_when_budidaya_is_enabled(): void
    {
        $company = Company::factory()->create(); // default: enabled
        $owner = $this->makeUserWithRole($company, 'owner', ['report.view', 'activity.view']);

        $response = $this->actingAs($owner)->get('/dashboard');

        $response->assertSee('>Greenhouse</span>', false)
            ->assertSee('>Musim Tanam</span>', false);
    }

    public function test_user_without_company_update_permission_cannot_access_settings_page(): void
    {
        $company = Company::factory()->create();
        $worker = $this->makeUserWithRole($company, 'worker', []);

        Livewire::actingAs($worker)->test(ModuleToggle::class)->assertForbidden();
    }

    public function test_user_without_company_update_permission_does_not_see_the_settings_link(): void
    {
        $company = Company::factory()->create();
        $worker = $this->makeUserWithRole($company, 'worker', ['report.view', 'activity.view']);

        $response = $this->actingAs($worker)->get('/dashboard');

        $response->assertDontSee('Pengaturan Modul');
    }
}
