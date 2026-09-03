<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('employee'));
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'position' => ['nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'required', Rule::in(['active', 'inactive'])],
            'user_id' => [
                'sometimes', 'nullable',
                Rule::exists('users', 'id')->where('company_id', $companyId),
                Rule::unique('employees', 'user_id')->ignore($this->route('employee')),
            ],
        ];
    }
}
