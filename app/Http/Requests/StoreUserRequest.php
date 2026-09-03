<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            // Globally unique, matching the existing constraint used by
            // CompanyRegistrationService/AuthController — email is the
            // login identifier across the whole system, not per-company.
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role_id' => [
                'required',
                Rule::exists('roles', 'id')->where('company_id', $companyId),
            ],
            'supervisor_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('company_id', $companyId),
            ],
        ];
    }
}
