<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Grade::class);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('grades', 'name')->where('company_id', $this->user()->company_id),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
