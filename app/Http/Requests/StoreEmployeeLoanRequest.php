<?php

namespace App\Http\Requests;

use App\Models\EmployeeLoan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', EmployeeLoan::class);
    }

    public function rules(): array
    {
        return [
            'employee_id' => [
                'required', Rule::exists('employees', 'id')->where('company_id', $this->user()->company_id),
            ],
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
