<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('asset'));
    }

    public function rules(): array
    {
        return [
            'greenhouse_id' => [
                'nullable',
                Rule::exists('greenhouses', 'id')->where('company_id', $this->user()->company_id),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'category' => ['sometimes', 'required', 'string', 'max:100'],
            'purchase_date' => ['sometimes', 'required', 'date'],
            'value' => ['sometimes', 'required', 'numeric', 'min:0'],
            'chart_of_account_id' => [
                'nullable',
                Rule::exists('chart_of_accounts', 'id')->where('company_id', $this->user()->company_id)->where('type', 'asset'),
            ],
            'useful_life_years' => ['nullable', 'integer', 'min:0'],
            'status' => ['nullable', Rule::in(['active', 'disposed', 'under_maintenance'])],
            'notes' => ['nullable', 'string'],
        ];
    }
}
