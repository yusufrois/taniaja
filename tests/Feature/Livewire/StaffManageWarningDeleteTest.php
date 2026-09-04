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

/** Roadmap tambahan — "peringatan bisa dihapus, biar tidak menumpuk". */
class StaffManageWarningDeleteTest extends TestCase
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

    public function test_owner_with_warn_permission_can_delete_a_warning(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['user.view', 'user.warn']);
        $staff = User::factory()->create(['company_id' => $company->id]);
        $warning = $staff->warnings()->create([
            'company_id' => $company->id, 'issued_by' => $owner->id, 'reason' => 'Salah ketik, hapus',
        ]);

        Livewire::actingAs($owner)
            ->test(Manage::class)
            ->call('openWarn', $staff->id)
            ->call('deleteWarning', $warning->id);

        $this->assertDatabaseMissing('user_warnings', ['id' => $warning->id]);
    }
}
