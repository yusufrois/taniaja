<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreActivityTemplateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Managing items is part of managing the template itself.
        return $this->user()->can('update', $this->route('activity_template'));
    }

    public function rules(): array
    {
        return [
            'hst' => ['required', 'integer', 'min:0'],
            'activity_name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'material' => ['nullable', 'string', 'max:255'],
            'dosage' => ['nullable', 'string', 'max:100'],
            'unit' => ['nullable', 'string', 'max:50'],
            'instruction' => ['nullable', 'string'],
            'estimated_duration_minutes' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
