<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Expense\Manage;
use App\Models\Company;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExpenseManageTest extends TestCase
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

    public function test_owner_can_record_an_expense(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['expense.view', 'expense.create']);
        $category = ExpenseCategory::create(['company_id' => $company->id, 'name' => 'Pupuk']);

        Livewire::actingAs($owner)
            ->test(Manage::class)
            ->call('openCreate')
            ->set('expense_category_id', $category->id)
            ->set('date', now()->toDateString())
            ->set('amount', 150000)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('expenses', ['company_id' => $company->id, 'amount' => 150000]);
    }

    /**
     * "Expense (approve)" is a SEPARATE permission from create/update
     * — Supervisor can record an expense but can't approve it.
     */
    public function test_supervisor_cannot_approve_an_expense_but_finance_can(): void
    {
        $company = Company::factory()->create();
        $supervisor = $this->makeUserWithRole($company, 'supervisor', ['expense.view', 'expense.create']);
        $finance = $this->makeUserWithRole($company, 'finance', ['expense.view', 'expense.approve']);
        $category = ExpenseCategory::create(['company_id' => $company->id, 'name' => 'Pupuk']);
        $expense = Expense::create([
            'company_id' => $company->id, 'expense_category_id' => $category->id,
            'date' => now()->toDateString(), 'amount' => 100000,
        ]);

        Livewire::actingAs($supervisor)->test(Manage::class)->assertSet('canApprove', false);

        Livewire::actingAs($finance)
            ->test(Manage::class)
            ->call('approve', $expense->id)
            ->assertHasNoErrors();

        $this->assertNotNull($expense->fresh()->approved_at);
    }

    /**
     * cost.view gates the actual amount, same as everywhere else
     * (Fase B principle) — Supervisor can record expenses but not see
     * total figures.
     */
    public function test_amount_hidden_from_view_without_cost_permission(): void
    {
        $company = Company::factory()->create();
        $supervisor = $this->makeUserWithRole($company, 'supervisor', ['expense.view']);

        Livewire::actingAs($supervisor)->test(Manage::class)->assertSet('canViewCost', false);
    }

    public function test_worker_without_permission_cannot_view_expenses(): void
    {
        $company = Company::factory()->create();
        $worker = $this->makeUserWithRole($company, 'worker', []);

        $this->assertTrue($worker->cannot('viewAny', Expense::class));
    }
}
