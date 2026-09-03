<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'status' => ['sometimes', 'required', Rule::in(['active', 'inactive'])],
            'role_id' => [
                'sometimes', 'required',
                Rule::exists('roles', 'id')->where('company_id', $companyId),
            ],
            'supervisor_id' => [
                'sometimes', 'nullable',
                Rule::exists('users', 'id')->where('company_id', $companyId),
            ],
        ];
    }
}
