<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Dashboard;
use App\Livewire\Staff\Manage as StaffManage;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Roadmap tambahan — "staf tandai sudah baca, minta atasan konfirmasi,
 * lalu tidak muncul lagi di dashboard, supaya tidak memenuhi
 * dashboard". 2 tahap: acknowledge (staf) lalu confirm (atasan) —
 * baru setelah KEDUANYA selesai warning-nya berhenti muncul.
 */
class WarningAcknowledgmentTest extends TestCase
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

    public function test_warning_still_shows_on_dashboard_before_acknowledgment(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', []);
        $worker = $this->makeUserWithRole($company, 'worker', ['activity.view']);
        $worker->warnings()->create(['company_id' => $company->id, 'issued_by' => $owner->id, 'reason' => 'Terlambat']);

        Livewire::actingAs($worker)->test(Dashboard::class)->assertSee('Terlambat')->assertSee('Sudah Baca');
    }

    public function test_staff_can_acknowledge_their_own_warning(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', []);
        $worker = $this->makeUserWithRole($company, 'worker', ['activity.view']);
        $warning = $worker->warnings()->create(['company_id' => $company->id, 'issued_by' => $owner->id, 'reason' => 'Terlambat']);

        Livewire::actingAs($worker)->test(Dashboard::class)->call('acknowledgeWarning', $warning->id);

        $this->assertNotNull($warning->fresh()->acknowledged_at);
    }

    /** Still on the dashboard after acknowledgment alone — atasan hasn't confirmed yet. */
    public function test_warning_still_shows_after_acknowledgment_but_before_confirmation(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', []);
        $worker = $this->makeUserWithRole($company, 'worker', ['activity.view']);
        $warning = $worker->warnings()->create([
            'company_id' => $company->id, 'issued_by' => $owner->id, 'reason' => 'Terlambat',
            'acknowledged_at' => now(),
        ]);

        Livewire::actingAs($worker)
            ->test(Dashboard::class)
            ->assertSee('Terlambat')
            ->assertSee('Menunggu konfirmasi atasan')
            ->assertDontSee('Sudah Baca'); // the button itself is gone once already acknowledged
    }

    public function test_atasan_can_confirm_an_acknowledged_warning(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['user.view', 'user.warn']);
        $worker = User::factory()->create(['company_id' => $company->id]);
        $warning = $worker->warnings()->create([
            'company_id' => $company->id, 'issued_by' => $owner->id, 'reason' => 'Terlambat',
            'acknowledged_at' => now(),
        ]);

        Livewire::actingAs($owner)
            ->test(StaffManage::class)
            ->call('openWarn', $worker->id)
            ->call('confirmWarning', $warning->id);

        $warning->refresh();
        $this->assertNotNull($warning->confirmed_at);
        $this->assertEquals($owner->id, $warning->confirmed_by);
    }

    /** Atasan cannot confirm one the staff hasn't acknowledged yet — the whole point of the 2-step flow. */
    public function test_atasan_cannot_confirm_an_unacknowledged_warning(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['user.view', 'user.warn']);
        $worker = User::factory()->create(['company_id' => $company->id]);
        $warning = $worker->warnings()->create([
            'company_id' => $company->id, 'issued_by' => $owner->id, 'reason' => 'Terlambat',
            // deliberately NOT acknowledged
        ]);

        Livewire::actingAs($owner)
            ->test(StaffManage::class)
            ->call('openWarn', $worker->id)
            ->call('confirmWarning', $warning->id)
            ->assertStatus(422);

        $this->assertNull($warning->fresh()->confirmed_at);
    }

    /** The actual payoff: once BOTH steps are done, it finally disappears from the dashboard. */
    public function test_warning_disappears_from_dashboard_after_full_acknowledge_and_confirm_flow(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', []);
        $worker = $this->makeUserWithRole($company, 'worker', ['activity.view']);
        $worker->warnings()->create([
            'company_id' => $company->id, 'issued_by' => $owner->id, 'reason' => 'Sudah selesai diproses',
            'acknowledged_at' => now(), 'confirmed_at' => now(), 'confirmed_by' => $owner->id,
        ]);

        Livewire::actingAs($worker)->test(Dashboard::class)->assertDontSee('Sudah selesai diproses');
    }

    public function test_staff_cannot_acknowledge_another_staffs_warning(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', []);
        $workerA = $this->makeUserWithRole($company, 'worker', ['activity.view']);
        $workerB = $this->makeUserWithRole($company, 'worker', ['activity.view']);
        $warning = $workerA->warnings()->create(['company_id' => $company->id, 'issued_by' => $owner->id, 'reason' => 'Punya A']);

        // acknowledgeWarning() scopes strictly to auth()->user()->warnings(),
        // so looking up another user's warning ID correctly 404s
        // (ModelNotFoundException) rather than silently no-op-ing —
        // confirming workerB genuinely cannot reach it at all.
        try {
            Livewire::actingAs($workerB)->test(Dashboard::class)->call('acknowledgeWarning', $warning->id);
            $this->fail('Expected a ModelNotFoundException.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // expected
        }

        $this->assertNull($warning->fresh()->acknowledged_at);
    }
}
