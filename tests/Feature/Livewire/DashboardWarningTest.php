<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Dashboard;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Bug report: "ada tombol peringatan itu ketika diuser muncul dimana"
 * — a warned staff member previously had NO way to see their own
 * warnings. Now shown on their own Dashboard, no special permission
 * needed (it's their own data).
 */
class DashboardWarningTest extends TestCase
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

    public function test_a_warned_worker_sees_their_own_warning_on_their_dashboard(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', []);
        $worker = $this->makeUserWithRole($company, 'worker', ['activity.view']);

        $worker->warnings()->create([
            'company_id' => $company->id, 'issued_by' => $owner->id, 'reason' => 'Terlambat masuk kerja 3 kali',
        ]);

        Livewire::actingAs($worker)->test(Dashboard::class)->assertSee('Terlambat masuk kerja 3 kali');
    }

    public function test_a_worker_with_no_warnings_sees_no_warning_banner(): void
    {
        $company = Company::factory()->create();
        $worker = $this->makeUserWithRole($company, 'worker', ['activity.view']);

        Livewire::actingAs($worker)->test(Dashboard::class)->assertDontSee('Peringatan untuk Anda');
    }

    /** One worker's warning never leaks to another worker's dashboard. */
    public function test_a_worker_never_sees_another_workers_warning(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', []);
        $workerA = $this->makeUserWithRole($company, 'worker', ['activity.view']);
        $workerB = $this->makeUserWithRole($company, 'worker', ['activity.view']);

        $workerA->warnings()->create([
            'company_id' => $company->id, 'issued_by' => $owner->id, 'reason' => 'Rahasia milik A',
        ]);

        Livewire::actingAs($workerB)->test(Dashboard::class)->assertDontSee('Rahasia milik A');
    }
}
