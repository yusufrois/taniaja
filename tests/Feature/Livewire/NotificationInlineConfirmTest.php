<?php

namespace Tests\Feature\Livewire;

use App\Livewire\NotificationBell;
use App\Livewire\Staff\Manage as StaffManage;
use App\Models\Company;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Roadmap tambahan — saran pengguna: (1) konfirmasi peringatan
 * langsung dari bel notifikasi tanpa masuk Kelola Staf, dengan ikon
 * terpisah untuk "sudah dibaca" vs "hapus"; (2) riwayat peringatan di
 * Kelola Staf diurutkan terbaru di atas.
 *
 * Owner in these tests has no role/permission attached (plain
 * factory user) — deliberately set as the worker's direct
 * SUPERVISOR instead, so these tests exercise the supervisor-based
 * authorization path specifically, not the role-permission path
 * (that's covered by SupervisorWarningTest already).
 */
class NotificationInlineConfirmTest extends TestCase
{
    use RefreshDatabase;

    public function test_atasan_can_confirm_a_warning_directly_from_the_bell(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->create(['company_id' => $company->id]);
        $worker = User::factory()->create(['company_id' => $company->id, 'supervisor_id' => $owner->id]);
        $warning = $worker->warnings()->create([
            'company_id' => $company->id, 'issued_by' => $owner->id, 'reason' => 'Terlambat',
            'acknowledged_at' => now(),
        ]);
        $notification = Notification::create([
            'company_id' => $company->id, 'user_id' => $owner->id, 'type' => 'warning_acknowledged',
            'title' => 'Sudah dibaca', 'body' => '-', 'data' => ['warning_id' => $warning->id, 'user_id' => $worker->id],
        ]);

        Livewire::actingAs($owner)->test(NotificationBell::class)->call('confirmWarning', $notification->id);

        $warning->refresh();
        $this->assertNotNull($warning->confirmed_at);
        $this->assertEquals($owner->id, $warning->confirmed_by);
        // Acting on it also clears the notification itself.
        $this->assertNotNull($notification->fresh()->read_at);
    }

    /** Same supervisor-or-permission rule as everywhere else this action exists. */
    public function test_unrelated_user_cannot_confirm_via_the_bell(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->create(['company_id' => $company->id]);
        $worker = User::factory()->create(['company_id' => $company->id, 'supervisor_id' => $owner->id]);
        $unrelatedUser = User::factory()->create(['company_id' => $company->id]); // NOT worker's supervisor
        $warning = $worker->warnings()->create([
            'company_id' => $company->id, 'issued_by' => $owner->id, 'reason' => 'Terlambat',
            'acknowledged_at' => now(),
        ]);
        // Deliberately created under the UNRELATED user's own notifications,
        // simulating them somehow having the ID (should still be blocked).
        $notification = Notification::create([
            'company_id' => $company->id, 'user_id' => $unrelatedUser->id, 'type' => 'warning_acknowledged',
            'title' => 'x', 'body' => '-', 'data' => ['warning_id' => $warning->id, 'user_id' => $worker->id],
        ]);

        Livewire::actingAs($unrelatedUser)
            ->test(NotificationBell::class)
            ->call('confirmWarning', $notification->id)
            ->assertStatus(403);

        $this->assertNull($warning->fresh()->confirmed_at);
    }

    public function test_cannot_confirm_via_bell_if_not_yet_acknowledged(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->create(['company_id' => $company->id]);
        $worker = User::factory()->create(['company_id' => $company->id, 'supervisor_id' => $owner->id]);
        $warning = $worker->warnings()->create([
            'company_id' => $company->id, 'issued_by' => $owner->id, 'reason' => 'Terlambat',
            // deliberately NOT acknowledged
        ]);
        $notification = Notification::create([
            'company_id' => $company->id, 'user_id' => $owner->id, 'type' => 'warning_acknowledged',
            'title' => 'x', 'body' => '-', 'data' => ['warning_id' => $warning->id, 'user_id' => $worker->id],
        ]);

        Livewire::actingAs($owner)
            ->test(NotificationBell::class)
            ->call('confirmWarning', $notification->id)
            ->assertStatus(422);
    }

    public function test_warning_history_is_sorted_newest_first(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->create(['company_id' => $company->id]);
        $worker = User::factory()->create(['company_id' => $company->id, 'supervisor_id' => $owner->id]);
        $worker->warnings()->create(['company_id' => $company->id, 'issued_by' => $owner->id, 'reason' => 'Peringatan Pertama']);
        $worker->warnings()->create(['company_id' => $company->id, 'issued_by' => $owner->id, 'reason' => 'Peringatan Kedua']);
        $worker->warnings()->create(['company_id' => $company->id, 'issued_by' => $owner->id, 'reason' => 'Peringatan Ketiga']);

        $component = Livewire::actingAs($owner)->test(StaffManage::class)->call('openWarn', $worker->id);

        $reasons = $component->viewData('warningTarget')->warnings->pluck('reason')->all();
        $this->assertEquals(['Peringatan Ketiga', 'Peringatan Kedua', 'Peringatan Pertama'], $reasons);
    }
}
