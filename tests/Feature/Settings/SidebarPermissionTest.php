<?php

namespace Tests\Feature\Settings;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bug report: "menu tetap muncul tapi ketika diklik blank 403" —
 * sidebar links were only gated by company-level module toggles
 * (Budidaya) or not gated at all, never by the LOGGED-IN USER'S OWN
 * role permissions. A Worker or Finance account would see a link,
 * click it, and only THEN find out they don't actually have access.
 */
class SidebarPermissionTest extends TestCase
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

    /** Worker (per the Role Matrix) has NO greenhouse/season/keuangan/report permissions at all. */
    public function test_worker_does_not_see_links_they_have_no_permission_for(): void
    {
        $company = Company::factory()->create();
        $worker = $this->makeUserWithRole($company, 'worker', ['activity.view']);

        $response = $this->actingAs($worker)->get('/dashboard');

        $response->assertDontSee('>Greenhouse</span>', false)
            ->assertDontSee('>Musim Tanam</span>', false)
            ->assertDontSee('>Beban</span>', false)
            ->assertDontSee('>Laporan</span>', false)
            ->assertDontSee('>Kelola Staf</span>', false);
    }

    /** Finance has money modules but NOT greenhouse/season (matches the Role Matrix). */
    public function test_finance_sees_keuangan_links_but_not_budidaya_links(): void
    {
        $company = Company::factory()->create();
        $finance = $this->makeUserWithRole($company, 'finance', [
            'expense.view', 'capital.view', 'debt.view', 'asset.view', 'accounting.view', 'report.view',
        ]);

        $response = $this->actingAs($finance)->get('/dashboard');

        $response->assertSee('>Beban</span>', false)
            ->assertSee('>Modal</span>', false)
            ->assertSee('>Laporan</span>', false)
            ->assertDontSee('>Greenhouse</span>', false)
            ->assertDontSee('>Musim Tanam</span>', false);
    }

    /** Owner (full access) sees everything — no regression from this fix. */
    public function test_owner_sees_every_link(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', [
            'greenhouse.view', 'season.view', 'crop.view', 'expense.view', 'capital.view',
            'debt.view', 'asset.view', 'accounting.view', 'report.view', 'user.view',
        ]);

        $response = $this->actingAs($owner)->get('/dashboard');

        $response->assertSee('>Greenhouse</span>', false)
            ->assertSee('>Musim Tanam</span>', false)
            ->assertSee('>Beban</span>', false)
            ->assertSee('>Laporan</span>', false)
            ->assertSee('>Kelola Staf</span>', false);
    }

    /** The Data Master group header itself disappears when NONE of its items are accessible. */
    public function test_data_master_group_header_is_hidden_when_no_items_are_accessible(): void
    {
        $company = Company::factory()->create();
        $worker = $this->makeUserWithRole($company, 'worker', ['activity.view']);

        $response = $this->actingAs($worker)->get('/dashboard');

        $response->assertDontSee('Data Master');
    }
}
