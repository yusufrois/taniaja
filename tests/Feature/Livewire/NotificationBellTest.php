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
 * Roadmap tambahan — "notifikasi bisa dihapus, karena kalau tidak
 * akan semakin banyak". No notification UI existed anywhere before
 * this (only the raw API).
 */
class NotificationBellTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_delete_their_own_notification(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $notification = Notification::create([
            'company_id' => $company->id, 'user_id' => $user->id,
            'type' => 'task.assigned', 'title' => 'Tugas Baru', 'body' => 'Anda ditugaskan sesuatu',
        ]);

        Livewire::actingAs($user)->test(NotificationBell::class)->call('delete', $notification->id);

        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    public function test_user_cannot_delete_another_users_notification(): void
    {
        $company = Company::factory()->create();
        $userA = User::factory()->create(['company_id' => $company->id]);
        $userB = User::factory()->create(['company_id' => $company->id]);
        $notification = Notification::create([
            'company_id' => $company->id, 'user_id' => $userA->id,
            'type' => 'task.assigned', 'title' => 'Punya A', 'body' => 'Rahasia',
        ]);

        // delete() scopes strictly to auth()->user()->notifications(),
        // so looking up another user's notification ID correctly
        // 404s (ModelNotFoundException) rather than silently no-op-ing
        // — confirming userB genuinely cannot reach it at all.
        try {
            Livewire::actingAs($userB)->test(NotificationBell::class)->call('delete', $notification->id);
            $this->fail('Expected a ModelNotFoundException.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // expected
        }

        $this->assertDatabaseHas('notifications', ['id' => $notification->id]);
    }

    public function test_clear_read_only_removes_read_notifications(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $read = Notification::create([
            'company_id' => $company->id, 'user_id' => $user->id,
            'type' => 'task.assigned', 'title' => 'Sudah dibaca', 'body' => '-', 'read_at' => now(),
        ]);
        $unread = Notification::create([
            'company_id' => $company->id, 'user_id' => $user->id,
            'type' => 'task.assigned', 'title' => 'Belum dibaca', 'body' => '-',
        ]);

        Livewire::actingAs($user)->test(NotificationBell::class)->call('clearRead');

        $this->assertDatabaseMissing('notifications', ['id' => $read->id]);
        $this->assertDatabaseHas('notifications', ['id' => $unread->id]);
    }

    public function test_unread_count_reflects_only_unread_notifications(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Notification::create(['company_id' => $company->id, 'user_id' => $user->id, 'type' => 'x', 'title' => 'A', 'body' => '-']);
        Notification::create(['company_id' => $company->id, 'user_id' => $user->id, 'type' => 'x', 'title' => 'B', 'body' => '-', 'read_at' => now()]);

        // unreadCount is data render() passes to the view, not a
        // public property — checked via viewData(), not assertSet().
        $component = Livewire::actingAs($user)->test(NotificationBell::class);
        $this->assertEquals(1, $component->viewData('unreadCount'));
    }
}
