<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGreenhouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Greenhouse::class);
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('greenhouses', 'code')->where('company_id', $this->user()->company_id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'length' => ['nullable', 'numeric', 'min:0'],
            'width' => ['nullable', 'numeric', 'min:0'],
            // Roadmap tambahan #5 — `area` is NOT accepted as direct
            // input anymore; Greenhouse::booted()'s saving hook always
            // (re)computes it from length × width. Removed from
            // rules() entirely so even if a client sends it, it's
            // silently dropped from validated() rather than fighting
            // the auto-calculation.
            'capacity' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'under_construction'])],
            'notes' => ['nullable', 'string'],
        ];
    }
}
