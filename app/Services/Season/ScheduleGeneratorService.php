<?php

namespace App\Services\Season;

use App\Models\ActivityTemplate;
use App\Models\Schedule;
use App\Models\Season;
use Illuminate\Support\Collection;

/**
 * Turns an ActivityTemplate's items into concrete Schedule rows for a
 * Season, per architecture doc Section 10:
 *   scheduled_date = season.planting_date + template_item.hst days
 *
 * Kept as a plain service class (not a job/event listener) per Aturan
 * #43 — this is a synchronous, fast operation with no need for a queue.
 */
class ScheduleGeneratorService
{
    /**
     * @return Collection<int, Schedule>
     */
    public function generate(Season $season, ?ActivityTemplate $template = null): Collection
    {
        $template ??= ActivityTemplate::where('variety_id', $season->variety_id)->first();

        if (! $template) {
            return collect();
        }

        // Idempotent: don't duplicate schedules if generate is called twice
        // for the same season (e.g. user double-clicks "Generate Schedule").
        if (Schedule::where('season_id', $season->id)->exists()) {
            return collect();
        }

        return $template->items->map(function ($item) use ($season) {
            return Schedule::create([
                'company_id' => $season->company_id,
                'season_id' => $season->id,
                'activity_template_item_id' => $item->id,
                'scheduled_date' => $season->planting_date->copy()->addDays($item->hst),
                'activity_name' => $item->activity_name,
                'category' => $item->category,
                'instruction' => $item->instruction,
                'status' => 'pending',
            ]);
        });
    }
}
