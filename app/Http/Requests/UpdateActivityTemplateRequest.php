<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateActivityTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('activity_template'));
    }

    public function rules(): array
    {
        return [
            'variety_id' => [
                'sometimes', 'required',
                Rule::exists('varieties', 'id')->where('company_id', $this->user()->company_id),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
        ];
    }
}
