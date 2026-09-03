<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Self check-in — for the employee checking THEMSELVES in. No
 * employee_id field: always resolves to the caller's own linked
 * Employee record (see AttendanceController::checkIn()).
 */
class CheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        // No permission gate beyond "has a linked Employee record" —
        // ANY logged-in employee should be able to check themselves
        // in, regardless of role. The actual "do you have an Employee
        // record" check happens in the controller (a clearer error
        // there than a generic 403 here).
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['hadir', 'izin', 'sakit'])], // never self-report "alpa"
            // GPS only matters as proof when physically present — not
            // required when self-reporting izin/sakit from home.
            'check_in_lat' => ['required_if:status,hadir', 'nullable', 'numeric', 'between:-90,90'],
            'check_in_lng' => ['required_if:status,hadir', 'nullable', 'numeric', 'between:-180,180'],
            'notes' => ['required_if:status,izin,sakit', 'nullable', 'string', 'max:500'],
        ];
    }
}
