<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Crop\Manage as CropManage;
use App\Livewire\Customer\Manage as CustomerManage;
use App\Livewire\ExpenseCategory\Manage as ExpenseCategoryManage;
use App\Livewire\Grade\Manage as GradeManage;
use App\Livewire\Supplier\Manage as SupplierManage;
use App\Livewire\Variety\Manage as VarietyManage;
use App\Models\Company;
use App\Models\Crop;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MasterDataManageTest extends TestCase
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

    public function test_owner_can_create_a_crop(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['crop.view', 'crop.create']);

        Livewire::actingAs($owner)
            ->test(CropManage::class)
            ->call('openCreate')
            ->set('name', 'Melon')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('crops', ['company_id' => $company->id, 'name' => 'Melon']);
    }

    /**
     * Variety's distinguishing behavior: it belongs to a Crop, and the
     * list can be filtered by ?crop_id= (linked from Crop's "Varietas"
     * button).
     */
    public function test_owner_can_create_a_variety_under_a_crop(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['variety.view', 'variety.create']);
        $crop = Crop::create(['company_id' => $company->id, 'name' => 'Melon']);

        Livewire::actingAs($owner)
            ->test(VarietyManage::class)
            ->call('openCreate')
            ->set('crop_id', $crop->id)
            ->set('name', 'Honey Globe')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('varieties', ['company_id' => $company->id, 'crop_id' => $crop->id, 'name' => 'Honey Globe']);
    }

    public function test_owner_can_create_a_supplier(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['supplier.view', 'supplier.create']);

        Livewire::actingAs($owner)
            ->test(SupplierManage::class)
            ->call('openCreate')
            ->set('name', 'Pak Tani Slamet')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('suppliers', ['company_id' => $company->id, 'name' => 'Pak Tani Slamet']);
    }

    /**
     * Customer's distinguishing behavior: the 'type' enum
     * (individual/business).
     */
    public function test_owner_can_create_a_business_customer(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['customer.view', 'customer.create']);

        Livewire::actingAs($owner)
            ->test(CustomerManage::class)
            ->call('openCreate')
            ->set('name', 'Toko Buah Segar')
            ->set('type', 'business')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('customers', ['company_id' => $company->id, 'name' => 'Toko Buah Segar', 'type' => 'business']);
    }

    /**
     * Grade's distinguishing behavior: sort_order controls display
     * order, and unique-name validation.
     */
    public function test_duplicate_grade_name_within_same_company_is_rejected(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['grade.view', 'grade.create']);
        \App\Models\Grade::create(['company_id' => $company->id, 'name' => 'Grade A']);

        Livewire::actingAs($owner)
            ->test(GradeManage::class)
            ->call('openCreate')
            ->set('name', 'Grade A')
            ->call('save')
            ->assertHasErrors(['name']);
    }

    public function test_owner_can_create_an_expense_category(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['expense_category.view', 'expense_category.create']);

        Livewire::actingAs($owner)
            ->test(ExpenseCategoryManage::class)
            ->call('openCreate')
            ->set('name', 'Pupuk & Nutrisi')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('expense_categories', ['company_id' => $company->id, 'name' => 'Pupuk & Nutrisi']);
    }

    /**
     * Cross-cutting authorization check, done once at the Policy level
     * (not repeated per-module — the underlying mechanism is identical
     * across all 6, already proven for Greenhouse in the earlier test
     * file, and per-module here would just be redundant repetition).
     */
    public function test_worker_without_permission_cannot_view_any_master_data_module(): void
    {
        $company = Company::factory()->create();
        $worker = $this->makeUserWithRole($company, 'worker', []);

        $this->assertTrue($worker->cannot('viewAny', \App\Models\Crop::class));
        $this->assertTrue($worker->cannot('viewAny', \App\Models\Variety::class));
        $this->assertTrue($worker->cannot('viewAny', \App\Models\Supplier::class));
        $this->assertTrue($worker->cannot('viewAny', \App\Models\Customer::class));
        $this->assertTrue($worker->cannot('viewAny', \App\Models\Grade::class));
        $this->assertTrue($worker->cannot('viewAny', \App\Models\ExpenseCategory::class));
    }
}
