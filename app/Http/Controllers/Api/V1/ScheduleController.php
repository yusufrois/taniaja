<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\LogsAudit;
use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use App\Http\Resources\ScheduleResource;
use App\Models\Activity;
use App\Models\Schedule;
use App\Models\Season;
use App\Services\Season\ScheduleGeneratorService;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    use LogsAudit;

    public function index(Season $season)
    {
        // Same reasoning as ActivityController::index() — schedule
        // visibility ("what do I need to do today") is an operational
        // 'activity' permission, available to Worker, not gated by
        // 'season' access which Worker doesn't have.
        $this->authorize('viewAny', Activity::class);

        return ScheduleResource::collection(
            $season->schedules()->orderBy('scheduled_date')->get()
        );
    }

    /**
     * POST /seasons/{season}/generate-schedule — Section 10.
     * Explicit action (not automatic on season creation) so the person
     * stays in control of when schedules are generated, and can create
     * a season first, adjust dates, then generate once ready.
     * Authorized against Season (not Activity) because generating the
     * schedule plan is a planning decision — same access level as
     * editing the season itself, not day-to-day activity logging.
     */
    public function generate(Season $season, ScheduleGeneratorService $generator)
    {
        $this->authorize('update', $season);

        $schedules = $generator->generate($season);

        if ($schedules->isEmpty()) {
            return response()->json([
                'message' => 'Tidak ada jadwal yang dibuat — sudah ada jadwal sebelumnya, '.
                    'atau varietas ini belum punya Activity Template.',
            ], 422);
        }

        $this->logAudit('schedule.generate', $season, null, ['generated_count' => $schedules->count()]);

        return ScheduleResource::collection($schedules);
    }

    /**
     * POST /schedules/{schedule}/complete
     * Marks the schedule completed AND creates its Activity completion
     * record in one step (Section 11: every completed Scheduled Activity
     * has a corresponding Activity row, same as an Ad Hoc one — the only
     * difference is schedule_id being set).
     *
     * Authorized against Activity::class (module 'activity'), NOT the
     * Season — per the Role & Permission Matrix, "Jalankan Scheduled
     * Activity" is something Owner/Manager/Supervisor/Worker can all do,
     * while editing a Season itself is Owner/Manager only (Supervisor
     * can only propose, Worker not at all). Checking against Season's
     * policy here would wrongly lock Worker out of their one core task.
     */
    public function complete(Request $request, Schedule $schedule)
    {
        $this->authorize('create', Activity::class);

        abort_if($schedule->status === 'completed', 422, 'Jadwal ini sudah selesai.');

        $validated = $request->validate([
            'cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $activity = Activity::create([
            'company_id' => $schedule->company_id,
            'season_id' => $schedule->season_id,
            'schedule_id' => $schedule->id,
            'date' => now()->toDateString(),
            'hst_snapshot' => $schedule->season->planting_date->diffInDays(now(), false),
            'category' => $schedule->category,
            'description' => $schedule->activity_name,
            'cost' => $validated['cost'] ?? 0,
            'notes' => $validated['notes'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        $schedule->update(['status' => 'completed']);
        $this->logAudit('schedule.complete', $schedule, null, ['activity_id' => $activity->id]);

        return new ActivityResource($activity);
    }

    public function skip(Schedule $schedule)
    {
        // Same reasoning as complete() — skipping is part of the
        // operational "run the schedule" workflow, so it's governed by
        // the 'activity' permission, not Season edit rights.
        $this->authorize('create', Activity::class);

        abort_if($schedule->status === 'completed', 422, 'Jadwal yang sudah selesai tidak bisa di-skip.');

        $schedule->update(['status' => 'skipped']);
        $this->logAudit('schedule.skip', $schedule);

        return new ScheduleResource($schedule);
    }
}
