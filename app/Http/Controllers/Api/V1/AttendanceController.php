<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\LogsAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckInRequest;
use App\Http\Requests\StoreAttendanceRequest;
use App\Http\Requests\UpdateAttendanceRequest;
use App\Http\Resources\AttendanceResource;
use App\Models\Attendance;

class AttendanceController extends Controller
{
    use LogsAudit;

    public function index()
    {
        $this->authorize('viewAny', Attendance::class);

        $query = Attendance::with(['employee', 'recorder'])->orderByDesc('date');

        // Same three-tier reasoning as Purchase/Sale/Expense (RBAC Fase
        // A), but scoped by employee_id (whose record is this ABOUT),
        // not created_by (who recorded it) — see AttendancePolicy.
        if (! auth()->user()->hasPermission('attendance.view')) {
            $myEmployeeId = auth()->user()->employee?->id;
            $query->where('employee_id', $myEmployeeId ?? 0); // 0 → empty result if no linked Employee
        }

        return AttendanceResource::collection($query->paginate(30));
    }

    /**
     * Self check-in — POST /attendances/check-in. Deliberately an
     * UPSERT (create-or-update), unlike store() below: if the caller
     * already has a record for today, this just corrects it in place.
     * No confusing duplicate error for someone fixing their own typo
     * or re-checking-in — per the person's explicit "salah input harus
     * gampang dibetulkan" principle. store() (marking someone ELSE)
     * stays strict, since silently overwriting another person's record
     * is a different, riskier situation.
     */
    public function checkIn(CheckInRequest $request)
    {
        $employee = $request->user()->employee;

        abort_if($employee === null, 422, 'Akun Anda belum ditautkan ke data pegawai. Hubungi admin/HRD.');

        $attendance = Attendance::updateOrCreate(
            ['employee_id' => $employee->id, 'date' => now()->toDateString()],
            [
                'company_id' => $request->user()->company_id,
                'status' => $request->validated('status'),
                'check_in_time' => $request->validated('status') === 'hadir' ? now()->format('H:i') : null,
                'check_in_lat' => $request->validated('check_in_lat'),
                'check_in_lng' => $request->validated('check_in_lng'),
                'notes' => $request->validated('notes'),
                'recorded_by' => $request->user()->id,
            ]
        );

        $this->logAudit('attendance.check_in', $attendance, null, $attendance->toArray());

        return new AttendanceResource($attendance->load(['employee', 'recorder']));
    }

    /**
     * Marking someone ELSE's attendance (no account / can't use a
     * phone). Strict create — see StoreAttendanceRequest for how a
     * duplicate attempt gets a friendly pointer to the correction path
     * (PUT) instead of a bare validation failure.
     */
    public function store(StoreAttendanceRequest $request)
    {
        $this->authorize('create', Attendance::class);

        $attendance = Attendance::create($request->validated() + [
            'company_id' => $request->user()->company_id,
            'recorded_by' => $request->user()->id,
        ]);

        $this->logAudit('create', $attendance, null, $attendance->toArray());

        return new AttendanceResource($attendance->load(['employee', 'recorder']));
    }

    public function show(Attendance $attendance)
    {
        $this->authorize('view', $attendance);

        return new AttendanceResource($attendance->load(['employee', 'recorder']));
    }

    /**
     * THE correction endpoint — change status/time/notes on a mistaken
     * entry instead of delete-then-recreate. This is the main answer
     * to "mudah dikoreksi kalau ada kesalahan".
     */
    public function update(UpdateAttendanceRequest $request, Attendance $attendance)
    {
        $old = $attendance->toArray();
        $attendance->update($request->validated());
        $this->logAudit('update', $attendance, $old, $attendance->toArray());

        return new AttendanceResource($attendance->load(['employee', 'recorder']));
    }

    /**
     * For when a record shouldn't exist at all (wrong day/wrong
     * person entirely) rather than needing a field corrected — soft
     * delete keeps the audit trail, matches Section 30's "void, don't
     * erase" principle used throughout this app.
     */
    public function destroy(Attendance $attendance)
    {
        $this->authorize('delete', $attendance);

        $attendance->delete();
        $this->logAudit('delete', $attendance);

        return response()->json(['message' => 'Catatan absensi berhasil dihapus.']);
    }
}
