<?php

namespace App\Http\Requests;

use App\Models\Debt;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDebtRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Debt::class);
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [
            'greenhouse_id' => ['nullable', Rule::exists('greenhouses', 'id')->where('company_id', $companyId)],
            'season_id' => ['nullable', Rule::exists('seasons', 'id')->where('company_id', $companyId)],
            'creditor_name' => ['required', 'string', 'max:255'],
            'debt_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:debt_date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'installment_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'chart_of_account_id' => [
                'nullable',
                Rule::exists('chart_of_accounts', 'id')->where('company_id', $companyId)->where('type', 'asset'),
            ],
            'notes' => ['nullable', 'string'],
        ];
    }
}
