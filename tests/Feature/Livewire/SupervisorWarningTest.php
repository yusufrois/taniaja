<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Dashboard;
use App\Livewire\Staff\Manage as StaffManage;
use App\Models\Company;
use App\Models\Notification;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Roadmap tambahan — 3 temuan sekaligus dari screenshot pengguna:
 * (1) Peringatan & Notifikasi disatukan (bel ikut menampilkan),
 * (2) atasan dapat Notifikasi balik saat staf klik "Sudah Baca",
 * (3) SEMUA atasan yang punya anak buah bisa kirim peringatan, bukan
 * cuma role dengan izin user.warn penuh — akun tetap cuma Owner yang
 * bisa buat.
 */
class SupervisorWarningTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithRole(Company $company, string $slug, array $permissionNames = [], ?int $supervisorId = null): User
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

        $user = User::factory()->create(['company_id' => $company->id, 'supervisor_id' => $supervisorId]);
        $user->roles()->attach($role->id);

        return $user;
    }

    public function test_issuing_a_warning_also_creates_a_notification(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['user.view', 'user.warn']);
        $worker = User::factory()->create(['company_id' => $company->id]);

        Livewire::actingAs($owner)
            ->test(StaffManage::class)
            ->call('openWarn', $worker->id)
            ->set('warningReason', 'Terlambat')
            ->call('submitWarning');

        $this->assertDatabaseHas('notifications', [
            'user_id' => $worker->id, 'type' => 'warning_issued',
        ]);
    }

    public function test_acknowledging_a_warning_notifies_the_issuer(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', []);
        $worker = $this->makeUserWithRole($company, 'worker', ['activity.view']);
        $warning = $worker->warnings()->create(['company_id' => $company->id, 'issued_by' => $owner->id, 'reason' => 'Terlambat']);

        Livewire::actingAs($worker)->test(Dashboard::class)->call('acknowledgeWarning', $warning->id);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $owner->id, 'type' => 'warning_acknowledged',
        ]);
    }

    /** The core fix: a Supervisor with NO user.warn permission can still warn their OWN subordinate. */
    public function test_supervisor_without_user_warn_permission_can_warn_their_own_subordinate(): void
    {
        $company = Company::factory()->create();
        $supervisor = $this->makeUserWithRole($company, 'supervisor', []); // deliberately no user.warn
        $worker = User::factory()->create(['company_id' => $company->id, 'supervisor_id' => $supervisor->id]);

        Livewire::actingAs($supervisor)
            ->test(StaffManage::class)
            ->call('openWarn', $worker->id)
            ->set('warningReason', 'Terlambat')
            ->call('submitWarning');

        $this->assertDatabaseHas('user_warnings', ['user_id' => $worker->id, 'issued_by' => $supervisor->id]);
    }

    /** But NOT someone who isn't their subordinate — supervision must be specific, not a blanket unlock. */
    public function test_supervisor_cannot_warn_someone_who_is_not_their_subordinate(): void
    {
        $company = Company::factory()->create();
        $supervisor = $this->makeUserWithRole($company, 'supervisor', []);
        User::factory()->create(['company_id' => $company->id, 'supervisor_id' => $supervisor->id]); // needed just so mount() lets them in at all
        $unrelatedWorker = User::factory()->create(['company_id' => $company->id, 'supervisor_id' => null]);

        Livewire::actingAs($supervisor)
            ->test(StaffManage::class)
            ->call('openWarn', $unrelatedWorker->id)
            ->assertStatus(403);
    }

    /** A supervisor with subordinates can now open Kelola Staf at all — previously Owner-only. */
    public function test_supervisor_with_subordinates_can_access_the_staff_page(): void
    {
        $company = Company::factory()->create();
        $supervisor = $this->makeUserWithRole($company, 'supervisor', []);
        User::factory()->create(['company_id' => $company->id, 'supervisor_id' => $supervisor->id]);

        Livewire::actingAs($supervisor)->test(StaffManage::class)->assertOk();
    }

    /** ...but sees ONLY their own team, never the whole company. */
    public function test_supervisors_staff_list_is_limited_to_their_own_team(): void
    {
        $company = Company::factory()->create();
        $supervisor = $this->makeUserWithRole($company, 'supervisor', []);
        $ownWorker = User::factory()->create(['company_id' => $company->id, 'supervisor_id' => $supervisor->id, 'name' => 'Anak Buah Saya']);
        $otherWorker = User::factory()->create(['company_id' => $company->id, 'supervisor_id' => null, 'name' => 'Bukan Anak Buah']);

        Livewire::actingAs($supervisor)
            ->test(StaffManage::class)
            ->assertSee('Anak Buah Saya')
            ->assertDontSee('Bukan Anak Buah');
    }

    /** A supervisor with NO subordinates at all and no user.view still can't open the page. */
    public function test_supervisor_with_no_subordinates_cannot_access_the_staff_page(): void
    {
        $company = Company::factory()->create();
        $lonelySupervisor = $this->makeUserWithRole($company, 'supervisor', []);

        $this->actingAs($lonelySupervisor)->get('/staff')->assertForbidden();
    }

    /** Account creation stays Owner-only even for a supervisor who CAN now warn their team. */
    public function test_supervisor_still_cannot_create_new_staff_accounts(): void
    {
        $company = Company::factory()->create();
        $supervisor = $this->makeUserWithRole($company, 'supervisor', []);
        User::factory()->create(['company_id' => $company->id, 'supervisor_id' => $supervisor->id]);

        Livewire::actingAs($supervisor)->test(StaffManage::class)->assertDontSee('Tambah Staf');
    }
}
