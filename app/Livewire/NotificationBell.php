<?php

namespace App\Livewire;

use App\Models\Notification;
use App\Models\UserWarning;
use Livewire\Component;

/**
 * Roadmap tambahan — no notification UI existed anywhere before this
 * (only the raw API: GET/PATCH /notifications). Embedded in the
 * shared layout's header so it's available on every page. Deliberately
 * self-scoped only — a notification is always personal, never
 * something another user (even Owner) should see/manage for someone
 * else, same principle NotificationController already followed.
 *
 * Roadmap tambahan #2 — saran pengguna: bisa Konfirmasi peringatan
 * LANGSUNG dari sini, tanpa masuk Kelola Staf. wire:poll.15s (lihat
 * view) membuat ini auto-refresh untuk atasan tanpa perlu pindah
 * halaman.
 */
class NotificationBell extends Component
{
    public bool $open = false;

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function markRead(int $id): void
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->update(['read_at' => now()]);
    }

    /** "notifikasi bisa dihapus, karena kalau tidak akan semakin banyak" */
    public function delete(int $id): void
    {
        auth()->user()->notifications()->findOrFail($id)->delete();
    }

    public function clearRead(): void
    {
        auth()->user()->notifications()->whereNotNull('read_at')->delete();
    }

    /**
     * Roadmap tambahan — atasan confirms a staff member's
     * acknowledgment straight from the bell dropdown, without
     * navigating to Kelola Staf. Same permission rule as everywhere
     * else this action exists (role-level 'user.warn' OR being that
     * staff member's direct supervisor) — a notification arriving in
     * someone's inbox is never itself authorization to act.
     */
    public function confirmWarning(int $notificationId): void
    {
        $notification = auth()->user()->notifications()->findOrFail($notificationId);
        $warningId = $notification->data['warning_id'] ?? null;
        abort_unless($warningId, 404);

        $warning = UserWarning::findOrFail($warningId);
        abort_if($warning->company_id !== auth()->user()->company_id, 404);

        $canConfirm = auth()->user()->hasPermission('user.warn') || auth()->user()->isSupervisorOf($warning->user);
        abort_unless($canConfirm, 403);
        abort_if(! $warning->acknowledged_at, 422);

        $warning->update(['confirmed_at' => now(), 'confirmed_by' => auth()->id()]);

        // The notification that prompted this has now been acted on
        // — clears it the same way markRead() would, so it doesn't
        // linger asking for an action that's already done.
        $notification->update(['read_at' => now()]);
    }

    /**
     * Roadmap tambahan — bug report: klik ✓ pada notifikasi "Anda
     * mendapat peringatan" tidak berefek apa-apa ke Peringatan
     * aslinya. Ini memicu alur "Sudah Baca" YANG SEBENARNYA (sama
     * seperti tombol di Dashboard) — bukan cuma menandai
     * Notifikasi-nya sendiri sebagai dibaca — termasuk mengirim
     * Notifikasi balik ke atasan yang menerbitkannya.
     */
    public function acknowledgeWarning(int $notificationId): void
    {
        $notification = auth()->user()->notifications()->findOrFail($notificationId);
        $warningId = $notification->data['warning_id'] ?? null;
        abort_unless($warningId, 404);

        $warning = UserWarning::findOrFail($warningId);
        abort_if($warning->user_id !== auth()->id(), 404);

        $warning->update(['acknowledged_at' => now()]);

        if ($warning->issued_by) {
            Notification::create([
                'company_id' => $warning->company_id,
                'user_id' => $warning->issued_by,
                'type' => 'warning_acknowledged',
                'title' => auth()->user()->name.' sudah membaca peringatan',
                'body' => 'Menunggu konfirmasi Anda.',
                'data' => ['warning_id' => $warning->id, 'user_id' => auth()->id()],
            ]);
        }

        $notification->update(['read_at' => now()]);
    }

    public function render()
    {
        $notifications = auth()->user()->notifications()->orderByDesc('id')->limit(15)->get();

        // For each 'warning_acknowledged' notification, resolve
        // whether ITS underlying warning still needs confirming —
        // lets the view show a "Konfirmasi" button only where it's
        // actually still actionable (not already confirmed by
        // someone, e.g. via Kelola Staf, since the notification was
        // last shown).
        $warningIds = $notifications->where('type', 'warning_acknowledged')
            ->pluck('data.warning_id')->filter()->all();
        $unconfirmedWarningIds = $warningIds
            ? UserWarning::whereIn('id', $warningIds)->whereNull('confirmed_at')->pluck('id')->all()
            : [];

        // Mirror for 'warning_issued' notifications — only show the
        // "Sudah Baca" action while the underlying warning hasn't
        // been acknowledged yet (e.g. not already done via Dashboard).
        $issuedWarningIds = $notifications->where('type', 'warning_issued')
            ->pluck('data.warning_id')->filter()->all();
        $unacknowledgedWarningIds = $issuedWarningIds
            ? UserWarning::whereIn('id', $issuedWarningIds)->whereNull('acknowledged_at')->pluck('id')->all()
            : [];

        return view('livewire.notification-bell', [
            'notifications' => $notifications,
            'unreadCount' => $notifications->whereNull('read_at')->count(),
            'hasRead' => $notifications->whereNotNull('read_at')->isNotEmpty(),
            'unconfirmedWarningIds' => $unconfirmedWarningIds,
            'unacknowledgedWarningIds' => $unacknowledgedWarningIds,
        ]);
    }
}
