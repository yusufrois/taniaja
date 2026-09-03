<?php

namespace App\Http\Requests;

use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Expense::class);
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [
            'greenhouse_id' => ['nullable', Rule::exists('greenhouses', 'id')->where('company_id', $companyId)],
            'season_id' => ['nullable', Rule::exists('seasons', 'id')->where('company_id', $companyId)],
            'purchase_id' => ['nullable', Rule::exists('purchases', 'id')->where('company_id', $companyId)],
            'expense_category_id' => ['required', Rule::exists('expense_categories', 'id')->where('company_id', $companyId)],
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->where('company_id', $companyId)],
            'date' => ['required', 'date'],
            'transaction_no' => ['nullable', 'string', 'max:100'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'chart_of_account_id' => [
                'nullable',
                Rule::exists('chart_of_accounts', 'id')->where('company_id', $companyId)->where('type', 'asset'),
            ],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
        ];
    }
}
