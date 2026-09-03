<?php

namespace App\Http\Requests;

use App\Models\InputItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInputItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', InputItem::class);
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('input_items', 'name')->where('company_id', $companyId),
            ],
            'unit' => ['required', 'string', 'max:50'],
            'category' => ['nullable', 'string', 'max:100'],
            // Used when an InputUsage of this item auto-creates an
            // Expense — see InputUsageController::store(). Optional:
            // an item with no default category simply won't generate
            // an Expense when used (still tracks stock, just not cost).
            'default_expense_category_id' => [
                'nullable', Rule::exists('expense_categories', 'id')->where('company_id', $companyId),
            ],
            'notes' => ['nullable', 'string'],
        ];
    }
}
