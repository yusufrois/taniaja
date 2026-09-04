<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Stock\Manage;
use App\Models\Company;
use App\Models\Crop;
use App\Models\Customer;
use App\Models\Grade;
use App\Models\Permission;
use App\Models\Role;
use App\Models\StockBatch;
use App\Models\User;
use App\Models\Variety;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Roadmap Fase UI-1 — halaman "Stok/Gudang". Menampilkan stok dari
 * kedua sumber (own_harvest & purchased) berdampingan, plus "Jual
 * Cepat" langsung dari StockBatchService::sell() yang sudah ada.
 */
class StockManageTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithRole(Company $company, string $slug, array $permissionNames = []): User
    {
        $role = Role::firstOrCreate(
            ['company_id' => $company->id, 'slug' => $slug],
            ['name' => ucfirst($slug)]
        );

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

    private function makeStockBatch(Company $company, string $sourceType, float $qty = 50): StockBatch
    {
        $crop = Crop::firstOrCreate(['company_id' => $company->id, 'name' => 'Melon']);
        $variety = Variety::firstOrCreate(['company_id' => $company->id, 'crop_id' => $crop->id, 'name' => 'Golden']);
        $grade = Grade::firstOrCreate(['company_id' => $company->id, 'name' => 'Grade A']);

        return StockBatch::create([
            'company_id' => $company->id, 'source_type' => $sourceType,
            'crop_id' => $crop->id, 'variety_id' => $variety->id, 'grade_id' => $grade->id,
            'acquired_date' => now()->toDateString(),
            'quantity_acquired' => $qty, 'quantity_available' => $qty,
            'unit_cost' => 5000, 'status' => 'active',
        ]);
    }

    public function test_owner_sees_stock_from_both_sources(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['sale.view']);
        $this->makeStockBatch($company, 'own_harvest');
        $this->makeStockBatch($company, 'purchased');

        $component = Livewire::actingAs($owner)->test(Manage::class);
        $this->assertCount(2, $component->viewData('batches'));
    }

    public function test_source_filter_narrows_to_own_harvest_only(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['sale.view']);
        $this->makeStockBatch($company, 'own_harvest');
        $this->makeStockBatch($company, 'purchased');

        $component = Livewire::actingAs($owner)->test(Manage::class)->set('sourceFilter', 'own_harvest');
        $batches = $component->viewData('batches');

        $this->assertCount(1, $batches);
        $this->assertEquals('own_harvest', $batches->first()->source_type);
    }

    public function test_owner_can_quick_sell_from_a_batch(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['sale.view', 'sale.create']);
        $batch = $this->makeStockBatch($company, 'own_harvest', 50);
        $customer = Customer::create(['company_id' => $company->id, 'name' => 'Toko Segar']);

        Livewire::actingAs($owner)
            ->test(Manage::class)
            ->call('openSell', $batch->id)
            ->set('customer_id', $customer->id)
            ->set('quantity_sold', 20)
            ->set('sale_price_per_unit', 15000)
            ->call('sell')
            ->assertHasNoErrors();

        $this->assertEquals(30, $batch->fresh()->quantity_available);
        $this->assertDatabaseHas('stock_batch_sales', ['stock_batch_id' => $batch->id, 'quantity_sold' => 20]);
    }

    public function test_selling_more_than_available_is_rejected(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['sale.view', 'sale.create']);
        $batch = $this->makeStockBatch($company, 'own_harvest', 10);

        Livewire::actingAs($owner)
            ->test(Manage::class)
            ->call('openSell', $batch->id)
            ->set('quantity_sold', 999)
            ->set('sale_price_per_unit', 15000)
            ->call('sell')
            ->assertHasErrors(['quantity_sold']);

        $this->assertEquals(10, $batch->fresh()->quantity_available);
    }

    public function test_worker_without_permission_cannot_view_stock_page(): void
    {
        $company = Company::factory()->create();
        $worker = $this->makeUserWithRole($company, 'worker', []);

        Livewire::actingAs($worker)->test(Manage::class)->assertForbidden();
    }

    public function test_unit_cost_hidden_without_cost_view_permission(): void
    {
        $company = Company::factory()->create();
        $warehouseUser = $this->makeUserWithRole($company, 'warehouse', ['sale.view']); // no cost.view
        $this->makeStockBatch($company, 'own_harvest');

        Livewire::actingAs($warehouseUser)->test(Manage::class)->assertDontSee('Harga Pokok');
    }
}
