<?php

namespace Tests\Feature\MasterData;

use App\Livewire\AssetCategory\Manage as AssetCategoryManage;
use App\Livewire\Crop\Manage as CropManage;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Company;
use App\Models\Crop;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Grade;
use App\Models\Greenhouse;
use App\Models\HarvestItem;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Season;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Variety;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Roadmap tambahan #9 — "jika ada kategori yang terhubung dengan
 * transaksi lain tidak boleh dihapus agar tidak error". Covers both
 * the API layer (GuardsAgainstReferencedDeletion trait) and the
 * Livewire layer (same ReferencedDeletionChecker service, since
 * Livewire's delete buttons bypass the API controllers entirely).
 */
class DeletionGuardTest extends TestCase
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

    public function test_api_rejects_deleting_an_expense_category_still_used_by_an_expense(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['expense_category.delete']);
        $category = ExpenseCategory::create(['company_id' => $company->id, 'name' => 'Pupuk']);
        Expense::create(['company_id' => $company->id, 'expense_category_id' => $category->id, 'date' => now()->toDateString(), 'amount' => 100000]);

        $response = $this->actingAs($owner)->deleteJson("/api/v1/expense-categories/{$category->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('expense_categories', ['id' => $category->id]);
    }

    public function test_api_allows_deleting_an_unused_expense_category(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['expense_category.delete']);
        $category = ExpenseCategory::create(['company_id' => $company->id, 'name' => 'Tidak Terpakai']);

        $this->actingAs($owner)->deleteJson("/api/v1/expense-categories/{$category->id}")->assertOk();

        $this->assertSoftDeleted('expense_categories', ['id' => $category->id]);
    }

    public function test_api_rejects_deleting_a_crop_still_used_by_a_variety(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['crop.delete']);
        $crop = Crop::create(['company_id' => $company->id, 'name' => 'Melon']);
        Variety::create(['company_id' => $company->id, 'crop_id' => $crop->id, 'name' => 'Golden']);

        $this->actingAs($owner)->deleteJson("/api/v1/crops/{$crop->id}")->assertStatus(422);
    }

    public function test_api_rejects_deleting_a_customer_still_used_by_a_sale(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['customer.delete']);
        $customer = Customer::create(['company_id' => $company->id, 'name' => 'Toko Buah Segar']);
        Sale::create(['company_id' => $company->id, 'customer_id' => $customer->id, 'invoice_number' => 'INV-1', 'date' => now()->toDateString(), 'total' => 100000]);

        $this->actingAs($owner)->deleteJson("/api/v1/customers/{$customer->id}")->assertStatus(422);
    }

    public function test_api_rejects_deleting_a_grade_still_used_by_a_harvest_item(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['grade.delete']);
        $grade = Grade::create(['company_id' => $company->id, 'name' => 'A']);
        $greenhouse = Greenhouse::create(['company_id' => $company->id, 'code' => 'GH-01', 'name' => 'GH A', 'status' => 'active']);
        $crop = Crop::create(['company_id' => $company->id, 'name' => 'Melon']);
        $variety = Variety::create(['company_id' => $company->id, 'crop_id' => $crop->id, 'name' => 'Golden']);
        $season = Season::create([
            'company_id' => $company->id, 'greenhouse_id' => $greenhouse->id,
            'crop_id' => $crop->id, 'variety_id' => $variety->id,
            'season_name' => 'Musim Uji', 'planting_date' => now()->toDateString(), 'status' => 'active',
        ]);
        $harvest = \App\Models\Harvest::create(['company_id' => $company->id, 'season_id' => $season->id, 'greenhouse_id' => $greenhouse->id, 'variety_id' => $variety->id, 'harvest_date' => now()->toDateString()]);
        HarvestItem::create(['company_id' => $company->id, 'harvest_id' => $harvest->id, 'grade_id' => $grade->id, 'quantity' => 10, 'weight' => 5]);

        $this->actingAs($owner)->deleteJson("/api/v1/grades/{$grade->id}")->assertStatus(422);
    }

    public function test_api_rejects_deleting_a_supplier_still_used_by_a_purchase(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['supplier.delete']);
        $supplier = Supplier::create(['company_id' => $company->id, 'name' => 'Pak Tani']);
        $crop = Crop::create(['company_id' => $company->id, 'name' => 'Melon']);
        \App\Models\Purchase::create([
            'company_id' => $company->id, 'supplier_id' => $supplier->id, 'crop_id' => $crop->id,
            'purchase_date' => now()->toDateString(), 'quantity' => 100, 'unit_price' => 1000, 'total_amount' => 100000,
        ]);

        $this->actingAs($owner)->deleteJson("/api/v1/suppliers/{$supplier->id}")->assertStatus(422);
    }

    /** The web (Livewire) delete button bypasses the API entirely — must be protected separately. */
    public function test_livewire_crop_delete_button_is_also_blocked_when_referenced(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['crop.view', 'crop.delete']);
        $crop = Crop::create(['company_id' => $company->id, 'name' => 'Tomat']);
        Variety::create(['company_id' => $company->id, 'crop_id' => $crop->id, 'name' => 'Cherry']);

        Livewire::actingAs($owner)
            ->test(CropManage::class)
            ->call('confirmDelete', $crop->id)
            ->call('delete')
            ->assertSet('deleteError', fn ($v) => str_contains($v, 'Varietas'));

        $this->assertDatabaseHas('crops', ['id' => $crop->id]); // NOT deleted
    }

    /** AssetCategory uses a name match (Asset.category is a string, not an FK) — different check path, needs its own test. */
    public function test_livewire_asset_category_delete_is_blocked_by_name_match_not_id(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['asset_category.view', 'asset_category.delete']);
        $category = AssetCategory::create(['company_id' => $company->id, 'name' => 'Peralatan']);
        Asset::create([
            'company_id' => $company->id, 'name' => 'Pompa Air', 'category' => 'Peralatan', // matches by NAME
            'purchase_date' => now()->toDateString(), 'value' => 1000000, 'status' => 'active',
        ]);

        Livewire::actingAs($owner)
            ->test(AssetCategoryManage::class)
            ->call('confirmDelete', $category->id)
            ->call('delete')
            ->assertSet('deleteError', fn ($v) => str_contains($v, 'Aset Tetap'));

        $this->assertDatabaseHas('asset_categories', ['id' => $category->id]);
    }

    public function test_livewire_crop_delete_succeeds_when_not_referenced(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['crop.view', 'crop.delete']);
        $crop = Crop::create(['company_id' => $company->id, 'name' => 'Tidak Terpakai']);

        Livewire::actingAs($owner)
            ->test(CropManage::class)
            ->call('confirmDelete', $crop->id)
            ->call('delete')
            ->assertSet('deleteError', null);

        $this->assertSoftDeleted('crops', ['id' => $crop->id]);
    }
}
