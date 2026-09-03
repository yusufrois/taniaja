<?php

namespace Tests\Feature\Company;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyProfileTest extends TestCase
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

    public function test_owner_can_view_and_update_company_profile(): void
    {
        $company = Company::factory()->create(['name' => 'Nama Lama']);
        $owner = $this->makeUserWithRole($company, 'owner', ['company.view', 'company.update']);

        $show = $this->actingAs($owner)->getJson('/api/v1/company');
        $show->assertOk();
        $this->assertEquals('Nama Lama', $show->json('data.name'));

        $update = $this->actingAs($owner)->patchJson('/api/v1/company', [
            'name' => 'Nama Baru', 'phone' => '081234567890',
        ]);
        $update->assertOk();
        $this->assertEquals('Nama Baru', $update->json('data.name'));
        $this->assertEquals('081234567890', $update->json('data.phone'));
    }

    /**
     * 'code' and 'status' are deliberately NOT editable via this
     * endpoint — code is baked into invoice/delivery numbering, and
     * status is admin/Super-Admin controlled (e.g. suspension).
     */
    public function test_code_and_status_cannot_be_changed_via_this_endpoint(): void
    {
        $company = Company::factory()->create(['code' => 'ORI123', 'status' => 'active']);
        $owner = $this->makeUserWithRole($company, 'owner', ['company.view', 'company.update']);

        $this->actingAs($owner)->patchJson('/api/v1/company', [
            'code' => 'HACKED', 'status' => 'suspended',
        ])->assertOk();

        $company->refresh();
        $this->assertEquals('ORI123', $company->code);
        $this->assertEquals('active', $company->status);
    }

    public function test_worker_without_permission_cannot_view_company_profile(): void
    {
        $company = Company::factory()->create();
        $worker = $this->makeUserWithRole($company, 'worker', []);

        $response = $this->actingAs($worker)->getJson('/api/v1/company');
        $response->assertForbidden();
    }
}
