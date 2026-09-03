<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInputItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('input_item'));
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [
            'name' => [
                'sometimes', 'required', 'string', 'max:255',
                Rule::unique('input_items', 'name')
                    ->where('company_id', $companyId)
                    ->ignore($this->route('input_item')),
            ],
            'unit' => ['sometimes', 'required', 'string', 'max:50'],
            'category' => ['nullable', 'string', 'max:100'],
            'default_expense_category_id' => [
                'sometimes', 'nullable', Rule::exists('expense_categories', 'id')->where('company_id', $companyId),
            ],
            'notes' => ['nullable', 'string'],
        ];
    }
}
