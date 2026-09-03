<?php

namespace Tests\Feature\Livewire;

use App\Livewire\ChartOfAccount\Manage;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChartOfAccountManageTest extends TestCase
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

    /**
     * The core payoff: this page was the ONLY way (besides raw
     * Postman API calls) for an existing company to ever get its
     * Chart of Accounts populated — without it, every "Dibayar dari
     * Akun" dropdown app-wide stays empty forever.
     */
    public function test_owner_can_seed_default_accounts_from_the_ui(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['accounting.view', 'accounting.create']);

        // hasAnyAccounts is data render() passes to the view, not a
        // public property — checked via viewData(), not assertSet().
        $component = Livewire::actingAs($owner)->test(Manage::class);
        $this->assertFalse($component->viewData('hasAnyAccounts'));

        $component->call('seedDefaults');
        $this->assertTrue($component->viewData('hasAnyAccounts'));

        $this->assertEquals(16, ChartOfAccount::where('company_id', $company->id)->count());
    }

    public function test_owner_can_add_a_custom_account(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['accounting.view', 'accounting.create']);

        Livewire::actingAs($owner)
            ->test(Manage::class)
            ->call('openCreate')
            ->set('code', '1120')
            ->set('name', 'Bank BCA')
            ->set('type', 'asset')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('chart_of_accounts', ['company_id' => $company->id, 'code' => '1120', 'name' => 'Bank BCA']);
    }

    public function test_worker_without_permission_cannot_view_chart_of_accounts(): void
    {
        $company = Company::factory()->create();
        $worker = $this->makeUserWithRole($company, 'worker', []);

        $this->assertTrue($worker->cannot('viewAny', ChartOfAccount::class));
    }
}
