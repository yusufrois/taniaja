<?php

namespace Tests\Feature\Access;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserWarningSuspendTest extends TestCase
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

    public function test_owner_can_issue_a_warning_to_a_staff_member(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['user.warn']);
        $staff = User::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($owner)->postJson("/api/v1/users/{$staff->id}/warnings", [
            'reason' => 'Terlambat input laporan 3 hari berturut-turut',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('user_warnings', [
            'user_id' => $staff->id,
            'issued_by' => $owner->id,
        ]);
    }

    public function test_user_without_warn_permission_gets_403(): void
    {
        $company = Company::factory()->create();
        $supervisor = $this->makeUserWithRole($company, 'supervisor', []); // no user.warn
        $staff = User::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($supervisor)->postJson("/api/v1/users/{$staff->id}/warnings", [
            'reason' => 'Percobaan tanpa izin',
        ]);

        $response->assertForbidden();
    }

    public function test_multiple_warnings_accumulate_and_are_listed_newest_first(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['user.warn']);
        $staff = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($owner)->postJson("/api/v1/users/{$staff->id}/warnings", ['reason' => 'Peringatan pertama'])->assertCreated();
        $this->actingAs($owner)->postJson("/api/v1/users/{$staff->id}/warnings", ['reason' => 'Peringatan kedua'])->assertCreated();

        $list = $this->actingAs($owner)->getJson("/api/v1/users/{$staff->id}/warnings");
        $list->assertOk();
        $this->assertCount(2, $list->json('data'));
        $this->assertEquals('Peringatan kedua', $list->json('data.0.reason')); // newest first
    }

    public function test_owner_can_suspend_and_the_user_stays_listed_as_inactive(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['user.update', 'user.view']);
        $staff = User::factory()->create(['company_id' => $company->id, 'status' => 'active']);

        $suspend = $this->actingAs($owner)->patchJson("/api/v1/users/{$staff->id}/suspend");
        $suspend->assertOk();
        $suspend->assertJsonPath('data.status', 'inactive');

        // Unlike destroy(), suspend does NOT soft-delete — still shows up in the list.
        $list = $this->actingAs($owner)->getJson('/api/v1/users');
        $ids = collect($list->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($staff->id));
    }

    public function test_suspended_user_cannot_log_in(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['user.update']);
        $staff = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'suspend-me@test.test',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        $this->actingAs($owner)->patchJson("/api/v1/users/{$staff->id}/suspend")->assertOk();

        $login = $this->postJson('/api/v1/login', ['email' => 'suspend-me@test.test', 'password' => 'password123']);
        $login->assertStatus(403);
    }

    public function test_owner_can_reactivate_a_suspended_user_and_they_can_log_in_again(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['user.update']);
        $staff = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'reactivate-me@test.test',
            'password' => Hash::make('password123'),
            'status' => 'inactive',
        ]);

        $reactivate = $this->actingAs($owner)->patchJson("/api/v1/users/{$staff->id}/reactivate");
        $reactivate->assertOk();
        $reactivate->assertJsonPath('data.status', 'active');

        $login = $this->postJson('/api/v1/login', ['email' => 'reactivate-me@test.test', 'password' => 'password123']);
        $login->assertOk();
    }

    public function test_owner_cannot_warn_or_suspend_a_user_from_a_different_company(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $ownerA = $this->makeUserWithRole($companyA, 'owner', ['user.warn', 'user.update']);
        $staffInB = User::factory()->create(['company_id' => $companyB->id]);

        $warn = $this->actingAs($ownerA)->postJson("/api/v1/users/{$staffInB->id}/warnings", ['reason' => 'Percobaan lintas company']);
        $warn->assertNotFound();

        $suspend = $this->actingAs($ownerA)->patchJson("/api/v1/users/{$staffInB->id}/suspend");
        $suspend->assertNotFound();

        $this->assertEquals('active', $staffInB->fresh()->status);
        $this->assertDatabaseCount('user_warnings', 0);
    }
}
