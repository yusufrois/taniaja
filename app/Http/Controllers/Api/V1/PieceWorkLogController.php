<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\LogsAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePieceWorkLogRequest;
use App\Http\Resources\PieceWorkLogResource;
use App\Models\PieceWorkLog;
use App\Models\WorkType;

class PieceWorkLogController extends Controller
{
    use LogsAudit;

    public function index()
    {
        $this->authorize('viewAny', PieceWorkLog::class);

        return PieceWorkLogResource::collection(
            PieceWorkLog::with(['employee', 'workType'])->orderByDesc('date')->paginate(30)
        );
    }

    /**
     * rate/amount FROZEN at entry time from WorkType.rate — see
     * PieceWorkLog migration docblock. A later change to WorkType.rate
     * must never retroactively change an already-logged day's pay.
     */
    public function store(StorePieceWorkLogRequest $request)
    {
        $this->authorize('create', PieceWorkLog::class);

        $validated = $request->validated();
        $workType = WorkType::findOrFail($validated['work_type_id']);
        $rate = (float) $workType->rate;
        $amount = round($validated['quantity'] * $rate, 2);

        $log = PieceWorkLog::create($validated + [
            'company_id' => $request->user()->company_id,
            'rate' => $rate,
            'amount' => $amount,
            'created_by' => $request->user()->id,
        ]);

        $this->logAudit('create', $log, null, $log->toArray());

        return new PieceWorkLogResource($log->load(['employee', 'workType']));
    }

    public function show(PieceWorkLog $piece_work_log)
    {
        $this->authorize('view', $piece_work_log);

        return new PieceWorkLogResource($piece_work_log->load(['employee', 'workType']));
    }

    /**
     * Soft delete — the "koreksi kesalahan" path for a wrongly-logged
     * work entry (wrong person/date/quantity entirely). For fixing just
     * the quantity, re-creating with the correct value + deleting the
     * wrong one is the current path (no update() endpoint yet — kept
     * minimal for this MVP; can add if a plain quantity fix without
     * losing the entry's identity turns out to matter).
     */
    public function destroy(PieceWorkLog $piece_work_log)
    {
        $this->authorize('delete', $piece_work_log);

        $piece_work_log->delete();
        $this->logAudit('delete', $piece_work_log);

        return response()->json(['message' => 'Catatan hasil kerja berhasil dihapus.']);
    }
}
