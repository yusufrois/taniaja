<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Capital\Manage;
use App\Models\CapitalTransaction;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CapitalManageTest extends TestCase
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

    public function test_owner_can_record_an_owner_investment(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['capital.view', 'capital.create']);

        Livewire::actingAs($owner)
            ->test(Manage::class)
            ->call('openCreate')
            ->set('type', 'owner_investment')
            ->set('date', now()->toDateString())
            ->set('amount', 5000000)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('capital_transactions', ['company_id' => $company->id, 'type' => 'owner_investment', 'amount' => 5000000]);
    }

    public function test_finance_can_record_a_withdrawal(): void
    {
        $company = Company::factory()->create();
        $finance = $this->makeUserWithRole($company, 'finance', ['capital.view', 'capital.create']);

        Livewire::actingAs($finance)
            ->test(Manage::class)
            ->call('openCreate')
            ->set('type', 'withdrawal')
            ->set('date', now()->toDateString())
            ->set('amount', 1000000)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('capital_transactions', ['type' => 'withdrawal', 'amount' => 1000000]);
    }

    public function test_owner_can_delete_a_capital_transaction(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['capital.view', 'capital.delete']);
        $transaction = CapitalTransaction::create([
            'company_id' => $company->id, 'type' => 'owner_investment',
            'date' => now()->toDateString(), 'amount' => 2000000,
        ]);

        Livewire::actingAs($owner)
            ->test(Manage::class)
            ->call('confirmDelete', $transaction->id)
            ->call('delete');

        $this->assertSoftDeleted('capital_transactions', ['id' => $transaction->id]);
    }

    public function test_amount_hidden_from_view_without_cost_permission(): void
    {
        $company = Company::factory()->create();
        $supervisor = $this->makeUserWithRole($company, 'supervisor', ['capital.view']);

        Livewire::actingAs($supervisor)->test(Manage::class)->assertSet('canViewCost', false);
    }

    public function test_worker_without_permission_cannot_view_capital_page(): void
    {
        $company = Company::factory()->create();
        $worker = $this->makeUserWithRole($company, 'worker', []);

        $this->assertTrue($worker->cannot('viewAny', CapitalTransaction::class));
    }
}
