<?php

namespace Tests\Feature\Livewire;

use App\Livewire\NotificationBell;
use App\Models\Company;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Bug report: klik ✓ pada notifikasi "Anda mendapat peringatan" tidak
 * berefek apa-apa ke Peringatan aslinya — cuma menandai Notifikasi
 * itu sendiri sebagai dibaca. Sekarang memicu alur "Sudah Baca" yang
 * sebenarnya (sama seperti tombol di Dashboard).
 */
class NotificationBellAcknowledgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_acknowledge_a_warning_directly_from_the_bell(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->create(['company_id' => $company->id]);
        $worker = User::factory()->create(['company_id' => $company->id]);
        $warning = $worker->warnings()->create(['company_id' => $company->id, 'issued_by' => $owner->id, 'reason' => 'Terlambat']);
        $notification = Notification::create([
            'company_id' => $company->id, 'user_id' => $worker->id, 'type' => 'warning_issued',
            'title' => 'Anda mendapat peringatan', 'body' => 'Terlambat', 'data' => ['warning_id' => $warning->id],
        ]);

        Livewire::actingAs($worker)->test(NotificationBell::class)->call('acknowledgeWarning', $notification->id);

        $this->assertNotNull($warning->fresh()->acknowledged_at);
        $this->assertNotNull($notification->fresh()->read_at);
    }

    /** The actual payoff: the issuer gets notified back, same as the Dashboard button does. */
    public function test_acknowledging_via_bell_notifies_the_issuer(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->create(['company_id' => $company->id]);
        $worker = User::factory()->create(['company_id' => $company->id]);
        $warning = $worker->warnings()->create(['company_id' => $company->id, 'issued_by' => $owner->id, 'reason' => 'Terlambat']);
        $notification = Notification::create([
            'company_id' => $company->id, 'user_id' => $worker->id, 'type' => 'warning_issued',
            'title' => 'Anda mendapat peringatan', 'body' => 'Terlambat', 'data' => ['warning_id' => $warning->id],
        ]);

        Livewire::actingAs($worker)->test(NotificationBell::class)->call('acknowledgeWarning', $notification->id);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $owner->id, 'type' => 'warning_acknowledged',
        ]);
    }

    public function test_staff_cannot_acknowledge_another_staffs_warning_via_bell(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->create(['company_id' => $company->id]);
        $workerA = User::factory()->create(['company_id' => $company->id]);
        $workerB = User::factory()->create(['company_id' => $company->id]);
        $warning = $workerA->warnings()->create(['company_id' => $company->id, 'issued_by' => $owner->id, 'reason' => 'Punya A']);
        // Notification deliberately placed in workerB's own inbox to
        // simulate them somehow having the ID — should still be blocked.
        $notification = Notification::create([
            'company_id' => $company->id, 'user_id' => $workerB->id, 'type' => 'warning_issued',
            'title' => 'x', 'body' => '-', 'data' => ['warning_id' => $warning->id],
        ]);

        // abort_if(...) throws an HTTP exception Livewire's test
        // harness catches and exposes via assertStatus() — no
        // try/catch needed here (unlike a bare findOrFail() 404,
        // which propagates as ModelNotFoundException instead).
        Livewire::actingAs($workerB)
            ->test(NotificationBell::class)
            ->call('acknowledgeWarning', $notification->id)
            ->assertStatus(404);

        $this->assertNull($warning->fresh()->acknowledged_at);
    }
}
