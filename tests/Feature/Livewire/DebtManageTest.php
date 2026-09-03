<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Debt\Manage;
use App\Models\Company;
use App\Models\Debt;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DebtManageTest extends TestCase
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

    public function test_owner_can_record_a_new_debt(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['debt.view', 'debt.create']);

        Livewire::actingAs($owner)
            ->test(Manage::class)
            ->call('openCreate')
            ->set('creditor_name', 'Bank BRI')
            ->set('debt_date', now()->toDateString())
            ->set('amount', 20000000)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('debts', ['company_id' => $company->id, 'creditor_name' => 'Bank BRI', 'amount' => 20000000]);
    }

    public function test_owner_can_record_a_payment_against_a_debt(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['debt.view', 'debt.create', 'debt.update']);
        $debt = Debt::create([
            'company_id' => $company->id, 'creditor_name' => 'Bank BRI',
            'debt_date' => now()->toDateString(), 'amount' => 10000000,
        ]);

        Livewire::actingAs($owner)
            ->test(Manage::class)
            ->call('openPayment', $debt->id)
            ->set('payment_date', now()->toDateString())
            ->set('payment_amount', 3000000)
            ->call('savePayment')
            ->assertHasNoErrors();

        $this->assertEquals(3000000, (float) $debt->fresh()->total_paid);
        $this->assertEquals(7000000, (float) $debt->fresh()->remaining);
    }

    /**
     * Same overpayment guard as the API — can never pay more than
     * what's still owed.
     */
    public function test_payment_exceeding_remaining_balance_is_rejected(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['debt.view', 'debt.create', 'debt.update']);
        $debt = Debt::create([
            'company_id' => $company->id, 'creditor_name' => 'Bank BRI',
            'debt_date' => now()->toDateString(), 'amount' => 5000000,
        ]);

        Livewire::actingAs($owner)
            ->test(Manage::class)
            ->call('openPayment', $debt->id)
            ->set('payment_date', now()->toDateString())
            ->set('payment_amount', 6000000) // more than the debt itself
            ->call('savePayment')
            ->assertHasErrors(['payment_amount']);
    }

    public function test_owner_can_delete_a_debt(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['debt.view', 'debt.delete']);
        $debt = Debt::create([
            'company_id' => $company->id, 'creditor_name' => 'Bank BRI',
            'debt_date' => now()->toDateString(), 'amount' => 1000000,
        ]);

        Livewire::actingAs($owner)
            ->test(Manage::class)
            ->call('confirmDelete', $debt->id)
            ->call('delete');

        $this->assertSoftDeleted('debts', ['id' => $debt->id]);
    }

    public function test_worker_without_permission_cannot_view_debts_page(): void
    {
        $company = Company::factory()->create();
        $worker = $this->makeUserWithRole($company, 'worker', []);

        $this->assertTrue($worker->cannot('viewAny', Debt::class));
    }
}
