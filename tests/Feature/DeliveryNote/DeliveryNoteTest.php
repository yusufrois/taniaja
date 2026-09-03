<?php

namespace Tests\Feature\DeliveryNote;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryNoteTest extends TestCase
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

    public function test_supervisor_can_create_a_delivery_note_with_items(): void
    {
        $company = Company::factory()->create();
        $supervisor = $this->makeUserWithRole($company, 'supervisor', ['delivery_note.create']);
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($supervisor)->postJson('/api/v1/delivery-notes', [
            'customer_id' => $customer->id,
            'date' => now()->toDateString(),
            'items' => [
                ['description' => 'Melon Grade A', 'quantity' => 50, 'unit' => 'kg'],
                ['description' => 'Melon Grade B', 'quantity' => 20, 'unit' => 'kg'],
            ],
        ]);

        $response->assertCreated();
        $this->assertStringStartsWith('SJ/', $response->json('data.delivery_number'));
        $this->assertCount(2, $response->json('data.items'));
    }

    public function test_delivery_number_increments_monthly_per_company(): void
    {
        $company = Company::factory()->create();
        $supervisor = $this->makeUserWithRole($company, 'supervisor', ['delivery_note.create']);
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $first = $this->actingAs($supervisor)->postJson('/api/v1/delivery-notes', [
            'customer_id' => $customer->id, 'date' => now()->toDateString(),
            'items' => [['description' => 'Melon', 'quantity' => 10, 'unit' => 'kg']],
        ]);
        $second = $this->actingAs($supervisor)->postJson('/api/v1/delivery-notes', [
            'customer_id' => $customer->id, 'date' => now()->toDateString(),
            'items' => [['description' => 'Melon', 'quantity' => 10, 'unit' => 'kg']],
        ]);

        $this->assertNotEquals($first->json('data.delivery_number'), $second->json('data.delivery_number'));
    }

    /**
     * The point of linking sale_id: payment status answers itself from
     * the linked Sale, no separate tracking needed — per the person's
     * question about "sudah lunas atau belum" after Surat Jalan issued.
     */
    public function test_delivery_note_linked_to_a_sale_shows_its_payment_status(): void
    {
        $company = Company::factory()->create();
        $customer = Customer::factory()->create(['company_id' => $company->id]);
        $owner = $this->makeUserWithRole($company, 'owner', [
            'delivery_note.create', 'delivery_note.view', 'sale.create',
        ]);

        $sale = $this->actingAs($owner)->postJson('/api/v1/sales', [
            'customer_id' => $customer->id, 'date' => now()->toDateString(),
            'items' => [['description' => 'Melon', 'quantity' => 30, 'price' => 15000]],
        ]);
        $saleId = $sale->json('data.id');

        $note = $this->actingAs($owner)->postJson('/api/v1/delivery-notes', [
            'customer_id' => $customer->id, 'sale_id' => $saleId, 'date' => now()->toDateString(),
            'items' => [['description' => 'Melon', 'quantity' => 30, 'unit' => 'kg']],
        ]);

        $note->assertCreated();
        $this->assertEquals('unpaid', $note->json('data.invoice_payment_status')); // no payment made yet
        $this->assertNotNull($note->json('data.invoice_number'));
    }

    public function test_worker_without_permission_cannot_create_delivery_note(): void
    {
        $company = Company::factory()->create();
        $worker = $this->makeUserWithRole($company, 'worker', []);
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($worker)->postJson('/api/v1/delivery-notes', [
            'customer_id' => $customer->id, 'date' => now()->toDateString(),
            'items' => [['description' => 'Melon', 'quantity' => 10, 'unit' => 'kg']],
        ]);

        $response->assertForbidden();
    }

    public function test_deleting_a_delivery_note_soft_deletes_it(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['delivery_note.create', 'delivery_note.delete']);
        $customer = Customer::factory()->create(['company_id' => $company->id]);

        $created = $this->actingAs($owner)->postJson('/api/v1/delivery-notes', [
            'customer_id' => $customer->id, 'date' => now()->toDateString(),
            'items' => [['description' => 'Melon', 'quantity' => 10, 'unit' => 'kg']],
        ]);
        $id = $created->json('data.id');

        $this->actingAs($owner)->deleteJson("/api/v1/delivery-notes/{$id}")->assertOk();
        $this->assertSoftDeleted('delivery_notes', ['id' => $id]);
    }
}
