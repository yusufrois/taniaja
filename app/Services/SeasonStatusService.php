<?php

namespace App\Services;

use App\Models\Season;
use Carbon\Carbon;

/**
 * Roadmap tambahan "Phase 10 — finalisasi API/Flutter readiness":
 * this logic previously lived ONLY inside the Season Livewire
 * component (web-only) — meaning a Flutter app calling the API
 * directly (POST/PUT /seasons) would NEVER get this automatic status
 * behavior, only whatever raw 'status' value it happened to send.
 * Extracted here so BOTH SeasonController (API — used by web's own
 * fetches too, and eventually Flutter) and the Livewire page call the
 * EXACT same logic — single source of truth, per the Phase 10 goal.
 */
class SeasonStatusService
{
    /**
     * "Saat user input panen pindah jadi panen" — but ONLY if that
     * date has actually arrived (bug report #8: a FUTURE-dated
     * actual_harvest_date must NOT prematurely flip status). Terminal
     * states (completed/cancelled) are the user's own explicit call
     * and are never overridden here.
     *
     * @param  array  $data  validated request data (status,
     *                       actual_harvest_date keys expected)
     * @return array the same array, with 'status' possibly overridden
     */
    public function applyHarvestTrigger(array $data): array
    {
        $actualHarvestDate = $data['actual_harvest_date'] ?? null;
        $status = $data['status'] ?? null;

        if (
            $actualHarvestDate
            && Carbon::parse($actualHarvestDate)->lte(now())
            && ! in_array($status, ['completed', 'cancelled'], true)
        ) {
            $data['status'] = 'harvesting';
        }

        return $data;
    }

    /**
     * "Saat tanggal tanam sesuai, otomatis pindah Aktif" — lazily
     * promoted whenever this is called (list/index load), same
     * "computed/checked on read rather than a scheduled cron job"
     * choice as before, now shared by both API and web.
     */
    public function promoteDueSeasons(int $companyId): void
    {
        Season::where('company_id', $companyId)
            ->where('status', 'planning')
            ->whereDate('planting_date', '<=', now())
            ->update(['status' => 'active']);
    }
}
