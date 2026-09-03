<?php

namespace Tests\Feature\Livewire;

use App\Livewire\AssetCategory\Manage;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AssetCategoryManageTest extends TestCase
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

    public function test_owner_can_create_an_asset_category(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['asset_category.view', 'asset_category.create']);

        Livewire::actingAs($owner)
            ->test(Manage::class)
            ->call('openCreate')
            ->set('name', 'Peralatan')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('asset_categories', ['company_id' => $company->id, 'name' => 'Peralatan']);
    }

    /**
     * The core payoff: the new category shows up as a selectable
     * option in the Asset form's dropdown.
     */
    public function test_asset_category_appears_as_a_selectable_option_on_the_asset_form(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', [
            'asset_category.view', 'asset_category.create', 'asset.view', 'asset.create',
        ]);

        Livewire::actingAs($owner)->test(Manage::class)
            ->call('openCreate')->set('name', 'Bangunan')->call('save');

        // ->get() only reads a component's PUBLIC PROPERTIES, not the
        // extra data render() passes to the view — so instead we open
        // the create modal (which renders the dropdown <option>s) and
        // check the actual rendered HTML contains the category.
        Livewire::actingAs($owner)
            ->test(\App\Livewire\Asset\Manage::class)
            ->call('openCreate')
            ->assertSee('Bangunan');
    }

    public function test_worker_without_permission_cannot_view_asset_categories(): void
    {
        $company = Company::factory()->create();
        $worker = $this->makeUserWithRole($company, 'worker', []);

        $this->assertTrue($worker->cannot('viewAny', \App\Models\AssetCategory::class));
    }
}
