<?php

namespace Tests\Feature\Audit;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
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
     * The core payoff: AuditLog rows have been accumulating via
     * LogsAudit since Phase 1 — this proves they're now actually
     * readable, not just silently piling up.
     */
    public function test_owner_can_view_audit_logs_generated_by_other_actions(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['customer.create', 'audit.view']);

        $this->actingAs($owner)->postJson('/api/v1/customers', [
            'name' => 'Toko Baru',
        ])->assertCreated();

        $response = $this->actingAs($owner)->getJson('/api/v1/audit-logs');
        $response->assertOk();

        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
        $this->assertTrue(collect($response->json('data'))->contains(fn ($log) => $log['model'] === 'App\\Models\\Customer'));
    }

    public function test_audit_logs_are_scoped_to_the_users_own_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $ownerA = $this->makeUserWithRole($companyA, 'owner', ['customer.create', 'audit.view']);
        $ownerB = $this->makeUserWithRole($companyB, 'owner', ['customer.create', 'audit.view']);

        $this->actingAs($ownerA)->postJson('/api/v1/customers', ['name' => 'Toko A'])->assertCreated();
        $this->actingAs($ownerB)->postJson('/api/v1/customers', ['name' => 'Toko B'])->assertCreated();

        $response = $this->actingAs($ownerA)->getJson('/api/v1/audit-logs');
        $response->assertOk();

        // Only company A's log should appear, never company B's.
        foreach ($response->json('data') as $log) {
            $this->assertNotEquals('Toko B', $log['new_value']['name'] ?? null);
        }
    }

    public function test_worker_without_permission_cannot_view_audit_logs(): void
    {
        $company = Company::factory()->create();
        $worker = $this->makeUserWithRole($company, 'worker', []);

        $response = $this->actingAs($worker)->getJson('/api/v1/audit-logs');
        $response->assertForbidden();
    }
}
