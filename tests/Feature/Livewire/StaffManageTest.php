<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Staff\Manage;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Roadmap tambahan — "Kelola Staf" web page, the first way to create
 * a Finance/Supervisor/Worker account through the UI (previously API
 * only) so those roles can actually be tried/tested end-to-end.
 */
class StaffManageTest extends TestCase
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

    public function test_owner_can_create_a_finance_staff_member(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['user.view', 'user.create']);
        $financeRole = Role::create(['company_id' => $company->id, 'slug' => 'finance', 'name' => 'Finance']);

        Livewire::actingAs($owner)
            ->test(Manage::class)
            ->call('openCreate')
            ->set('name', 'Budi Finance')
            ->set('email', 'budi@example.com')
            ->set('password', 'password123')
            ->set('role_id', $financeRole->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['company_id' => $company->id, 'email' => 'budi@example.com']);
    }

    /** The whole point — the newly created staff member can actually log in with their assigned role. */
    public function test_newly_created_staff_can_log_in(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['user.view', 'user.create']);
        $workerRole = Role::create(['company_id' => $company->id, 'slug' => 'worker', 'name' => 'Worker']);

        Livewire::actingAs($owner)
            ->test(Manage::class)
            ->call('openCreate')
            ->set('name', 'Slamet Worker')
            ->set('email', 'slamet@example.com')
            ->set('password', 'password123')
            ->set('role_id', $workerRole->id)
            ->call('save');

        $this->postJson('/api/v1/login', ['email' => 'slamet@example.com', 'password' => 'password123'])
            ->assertOk();
    }

    public function test_owner_can_edit_a_staff_members_role(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['user.view', 'user.update']);
        $oldRole = Role::create(['company_id' => $company->id, 'slug' => 'worker', 'name' => 'Worker']);
        $newRole = Role::create(['company_id' => $company->id, 'slug' => 'supervisor', 'name' => 'Supervisor']);
        $staff = User::factory()->create(['company_id' => $company->id]);
        $staff->roles()->attach($oldRole->id);

        Livewire::actingAs($owner)
            ->test(Manage::class)
            ->call('openEdit', $staff->id)
            ->set('role_id', $newRole->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals('Supervisor', $staff->fresh()->roles->first()->name);
    }

    public function test_owner_can_suspend_and_reactivate_a_staff_member(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['user.view', 'user.update']);
        $staff = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);

        Livewire::actingAs($owner)->test(Manage::class)->call('confirmSuspend', $staff->id)->call('suspend');
        $this->assertEquals('inactive', $staff->fresh()->status);

        Livewire::actingAs($owner)->test(Manage::class)->call('confirmReactivate', $staff->id)->call('reactivate');
        $this->assertEquals('active', $staff->fresh()->status);
    }

    public function test_owner_with_warn_permission_can_issue_a_warning(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['user.view', 'user.warn']);
        $staff = User::factory()->create(['company_id' => $company->id]);

        Livewire::actingAs($owner)
            ->test(Manage::class)
            ->call('openWarn', $staff->id)
            ->set('warningReason', 'Terlambat berulang kali')
            ->call('submitWarning')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('user_warnings', ['user_id' => $staff->id, 'reason' => 'Terlambat berulang kali']);
    }

    public function test_worker_without_permission_cannot_view_staff_page(): void
    {
        $company = Company::factory()->create();
        $worker = $this->makeUserWithRole($company, 'worker', []);

        $this->assertTrue($worker->cannot('viewAny', User::class));
    }
}
