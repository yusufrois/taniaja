<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGreenhouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('greenhouse'));
    }

    public function rules(): array
    {
        return [
            'code' => [
                'sometimes', 'required', 'string', 'max:50',
                Rule::unique('greenhouses', 'code')
                    ->where('company_id', $this->user()->company_id)
                    ->ignore($this->route('greenhouse')),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'length' => ['nullable', 'numeric', 'min:0'],
            'width' => ['nullable', 'numeric', 'min:0'],
            // Roadmap tambahan #5 — same as StoreGreenhouseRequest:
            // `area` is auto-computed by Greenhouse::booted(), not
            // accepted as direct input.
            'capacity' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'under_construction'])],
            'notes' => ['nullable', 'string'],
        ];
    }
}
