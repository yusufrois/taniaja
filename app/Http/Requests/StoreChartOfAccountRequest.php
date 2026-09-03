<?php

namespace App\Http\Requests;

use App\Models\ChartOfAccount;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * For adding a CUSTOM account beyond the standard 16 — the standard
 * ones are seeded automatically (StandardChartOfAccountsSeeder), this
 * is only for when a company needs something extra.
 */
class StoreChartOfAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ChartOfAccount::class);
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required', 'string', 'max:20',
                Rule::unique('chart_of_accounts', 'code')->where('company_id', $this->user()->company_id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['asset', 'liability', 'equity', 'revenue', 'expense'])],
        ];
    }
}
