<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Marking someone ELSE's attendance (for employees without an
 * account/phone) — needs 'attendance.create' permission, unlike
 * self check-in which anyone with a linked Employee can do.
 */
class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Attendance::class);
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('company_id', $companyId)],
            'date' => ['required', 'date'],
            'status' => ['required', Rule::in(['hadir', 'izin', 'sakit', 'alpa'])],
            'check_in_time' => ['nullable', 'date_format:H:i'],
            'check_in_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'check_in_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'notes' => ['required_if:status,izin,sakit', 'nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Friendly duplicate handling, per the person's own request that
     * mistakes should be easy to fix: rather than a bare "already
     * exists" error, points straight at the existing record's id so
     * the caller knows to PUT /attendances/{id} to correct it, instead
     * of guessing why their create failed.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $employeeId = $this->input('employee_id');
            $date = $this->input('date');

            if (! $employeeId || ! $date) {
                return;
            }

            $existing = Attendance::where('employee_id', $employeeId)
                ->whereDate('date', $date)
                ->first();

            if ($existing) {
                $validator->errors()->add(
                    'date',
                    "Absensi pegawai ini untuk tanggal tersebut sudah ada (id: {$existing->id}). ".
                    "Gunakan PUT /api/v1/attendances/{$existing->id} untuk mengoreksi, bukan buat baru."
                );
            }
        });
    }
}
