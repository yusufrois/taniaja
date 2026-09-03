<?php

namespace App\Http\Requests;

use App\Models\Activity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Covers Ad Hoc Activity creation (Section 11) — a Scheduled Activity's
 * completion record is created via ScheduleController::complete(),
 * which does NOT go through this request (it derives its fields from
 * the Schedule instead of accepting arbitrary user input for them).
 */
class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Activity::class);
    }

    public function rules(): array
    {
        return [
            'season_id' => [
                'required',
                Rule::exists('seasons', 'id')->where('company_id', $this->user()->company_id),
            ],
            'date' => ['required', 'date'],
            'category' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
