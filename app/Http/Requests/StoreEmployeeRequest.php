<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Employee::class);
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'position' => ['nullable', 'string', 'max:100'],
            // Optional — most employees in the "tidak punya akun" case
            // won't have one. If given, must be a User in the same
            // company who isn't already linked to a different Employee
            // (enforced by the table's unique constraint on user_id).
            'user_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('company_id', $companyId),
                Rule::unique('employees', 'user_id'),
            ],
        ];
    }
}
