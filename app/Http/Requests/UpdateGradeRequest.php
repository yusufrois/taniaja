<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('grade'));
    }

    public function rules(): array
    {
        return [
            'name' => [
                'sometimes', 'required', 'string', 'max:100',
                Rule::unique('grades', 'name')
                    ->where('company_id', $this->user()->company_id)
                    ->ignore($this->route('grade')),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
