<?php

namespace App\Http\Requests;

use App\Models\Debt;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDebtPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Debt $debt */
        $debt = $this->route('debt');

        return $this->user()->can('update', $debt);
    }

    public function rules(): array
    {
        return [
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'chart_of_account_id' => [
                'nullable',
                Rule::exists('chart_of_accounts', 'id')
                    ->where('company_id', $this->user()->company_id)
                    ->where('type', 'asset'),
            ],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * A debt payment can never exceed what's actually still owed —
     * otherwise `Debt::remaining` would go negative, which makes no
     * business sense (Aturan #48: correctness over convenience).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Debt $debt */
            $debt = $this->route('debt');
            $amount = (float) $this->input('amount');

            if ($amount > $debt->remaining) {
                $validator->errors()->add(
                    'amount',
                    "Jumlah pembayaran (Rp".number_format($amount, 0, ',', '.').
                    ") melebihi sisa hutang (Rp".number_format($debt->remaining, 0, ',', '.').").");
            }
        });
    }
}
