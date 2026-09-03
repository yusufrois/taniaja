<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('work_type'));
    }

    public function rules(): array
    {
        return [
            'name' => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('work_types', 'name')
                    ->where('company_id', $this->user()->company_id)
                    ->ignore($this->route('work_type')),
            ],
            'unit' => ['sometimes', 'required', 'string', 'max:50'],
            'rate' => ['sometimes', 'required', 'numeric', 'min:0'],
        ];
    }
}
