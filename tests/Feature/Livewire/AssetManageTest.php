<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Asset\Manage;
use App\Models\Asset;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AssetManageTest extends TestCase
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

    public function test_owner_can_add_an_asset(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['asset.view', 'asset.create']);

        Livewire::actingAs($owner)
            ->test(Manage::class)
            ->call('openCreate')
            ->set('name', 'Pompa Air Sumur Bor')
            ->set('category', 'Peralatan')
            ->set('purchase_date', now()->toDateString())
            ->set('value', 5000000)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('assets', ['company_id' => $company->id, 'name' => 'Pompa Air Sumur Bor']);
    }

    public function test_owner_can_edit_an_asset(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['asset.view', 'asset.update']);
        $asset = Asset::create([
            'company_id' => $company->id, 'name' => 'Nama Lama', 'category' => 'Peralatan',
            'purchase_date' => now()->toDateString(), 'value' => 1000000, 'status' => 'active',
        ]);

        Livewire::actingAs($owner)
            ->test(Manage::class)
            ->call('openEdit', $asset->id)
            ->set('status', 'under_maintenance')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals('under_maintenance', $asset->fresh()->status);
    }

    public function test_owner_can_delete_an_asset(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['asset.view', 'asset.delete']);
        $asset = Asset::create([
            'company_id' => $company->id, 'name' => 'Akan Dihapus', 'category' => 'Peralatan',
            'purchase_date' => now()->toDateString(), 'value' => 500000, 'status' => 'active',
        ]);

        Livewire::actingAs($owner)
            ->test(Manage::class)
            ->call('confirmDelete', $asset->id)
            ->call('delete');

        $this->assertSoftDeleted('assets', ['id' => $asset->id]);
    }

    public function test_worker_without_permission_cannot_view_assets_page(): void
    {
        $company = Company::factory()->create();
        $worker = $this->makeUserWithRole($company, 'worker', []);

        $this->assertTrue($worker->cannot('viewAny', Asset::class));
    }
}
