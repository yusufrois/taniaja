<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCropRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('crop'));
    }

    public function rules(): array
    {
        return [
            'name' => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('crops', 'name')
                    ->where('company_id', $this->user()->company_id)
                    ->ignore($this->route('crop')),
            ],
            'notes' => ['nullable', 'string'],
        ];
    }
}
