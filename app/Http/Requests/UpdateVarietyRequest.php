<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVarietyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('variety'));
    }

    public function rules(): array
    {
        return [
            'crop_id' => [
                'sometimes', 'required',
                Rule::exists('crops', 'id')->where('company_id', $this->user()->company_id),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
