<?php

namespace App\Http\Requests;

use App\Models\CapitalTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCapitalTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', CapitalTransaction::class);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['owner_investment', 'investor_investment', 'withdrawal'])],
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'chart_of_account_id' => [
                'nullable',
                Rule::exists('chart_of_accounts', 'id')
                    ->where('company_id', $this->user()->company_id)
                    ->where('type', 'asset'),
            ],
            'source' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
