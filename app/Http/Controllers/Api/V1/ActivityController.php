<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\LogsAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreActivityRequest;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Models\Season;

class ActivityController extends Controller
{
    use LogsAudit;

    public function index(Season $season)
    {
        // Listing a season's activities uses the 'activity' permission
        // (not 'season'), so Worker — who has activity.view/create but
        // no season.* permissions per the matrix — can still see the
        // work log for a season without being able to edit the season.
        $this->authorize('viewAny', Activity::class);

        return ActivityResource::collection(
            $season->activities()->orderByDesc('date')->paginate(20)
        );
    }

    /**
     * Ad Hoc Activity (Section 11) — schedule_id is always null here.
     * A Scheduled Activity's completion record is created exclusively
     * via ScheduleController::complete(), never through this endpoint.
     */
    public function store(StoreActivityRequest $request)
    {
        $this->authorize('create', Activity::class);

        $season = Season::findOrFail($request->validated('season_id'));

        $activity = Activity::create($request->validated() + [
            'company_id' => $request->user()->company_id,
            'schedule_id' => null,
            'hst_snapshot' => $season->planting_date->diffInDays($request->validated('date'), false),
            'created_by' => $request->user()->id,
        ]);

        $this->logAudit('create', $activity, null, $activity->toArray());

        return new ActivityResource($activity);
    }

    public function show(Activity $activity)
    {
        $this->authorize('view', $activity);

        return new ActivityResource($activity);
    }

    public function destroy(Activity $activity)
    {
        $this->authorize('delete', $activity);

        // If this was a Scheduled Activity's completion record, reopen
        // the schedule so it doesn't stay stuck at "completed" with no
        // underlying activity — keeps the two tables consistent.
        if ($activity->schedule_id) {
            $activity->schedule?->update(['status' => 'pending']);
        }

        $activity->delete();
        $this->logAudit('delete', $activity);

        return response()->json(['message' => 'Aktivitas berhasil dihapus.']);
    }
}
