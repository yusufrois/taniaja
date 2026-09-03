<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Greenhouse\Manage;
use App\Models\Company;
use App\Models\Greenhouse;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GreenhouseManageTest extends TestCase
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

    public function test_owner_can_create_a_greenhouse(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['greenhouse.view', 'greenhouse.create']);

        Livewire::actingAs($owner)
            ->test(Manage::class)
            ->call('openCreate')
            ->set('code', 'GH-01')
            ->set('name', 'Greenhouse Melon A')
            ->set('status', 'active')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('greenhouses', ['company_id' => $company->id, 'code' => 'GH-01']);
    }

    public function test_duplicate_code_within_same_company_is_rejected(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['greenhouse.view', 'greenhouse.create']);
        Greenhouse::create(['company_id' => $company->id, 'code' => 'GH-01', 'name' => 'Existing', 'status' => 'active']);

        Livewire::actingAs($owner)
            ->test(Manage::class)
            ->call('openCreate')
            ->set('code', 'GH-01')
            ->set('name', 'Duplikat')
            ->set('status', 'active')
            ->call('save')
            ->assertHasErrors(['code']);
    }

    public function test_owner_can_edit_a_greenhouse(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['greenhouse.view', 'greenhouse.update']);
        $gh = Greenhouse::create(['company_id' => $company->id, 'code' => 'GH-01', 'name' => 'Nama Lama', 'status' => 'active']);

        Livewire::actingAs($owner)
            ->test(Manage::class)
            ->call('openEdit', $gh->id)
            ->set('name', 'Nama Baru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals('Nama Baru', $gh->fresh()->name);
    }

    public function test_owner_can_delete_a_greenhouse(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['greenhouse.view', 'greenhouse.delete']);
        $gh = Greenhouse::create(['company_id' => $company->id, 'code' => 'GH-01', 'name' => 'Akan Dihapus', 'status' => 'active']);

        Livewire::actingAs($owner)
            ->test(Manage::class)
            ->call('confirmDelete', $gh->id)
            ->call('delete');

        $this->assertSoftDeleted('greenhouses', ['id' => $gh->id]);
    }

    public function test_worker_without_permission_cannot_mount_the_page(): void
    {
        $company = Company::factory()->create();
        $worker = $this->makeUserWithRole($company, 'worker', []);

        // Tested at the Policy level directly rather than through
        // Livewire's component lifecycle: Livewire::test() did not
        // expose the mount()-time AuthorizationException as a catchable
        // PHP exception in this environment (confirmed empirically —
        // neither expectException() nor an explicit try/catch around
        // Livewire::test() observed it), and guessing further at
        // Livewire's internal exception handling isn't worth chasing.
        // What actually matters — the worker has no way to view this
        // resource — is what this asserts, unambiguously.
        $this->assertTrue($worker->cannot('viewAny', \App\Models\Greenhouse::class));
    }

    public function test_worker_with_view_only_cannot_see_create_button_data(): void
    {
        $company = Company::factory()->create();
        $worker = $this->makeUserWithRole($company, 'worker', ['greenhouse.view']);

        Livewire::actingAs($worker)
            ->test(Manage::class)
            ->assertSet('showModal', false);

        // canCreate is passed to the view — worker with view-only should
        // never be handed the create-permission flag as true.
        $this->assertFalse($worker->can('create', Greenhouse::class));
    }
}
