<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The main "koreksi kesalahan" (fix mistakes) path for attendance —
 * change status/time/notes on an existing record instead of deleting
 * and recreating it.
 */
class UpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('attendance'));
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'required', Rule::in(['hadir', 'izin', 'sakit', 'alpa'])],
            'check_in_time' => ['nullable', 'date_format:H:i'],
            'check_in_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'check_in_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
