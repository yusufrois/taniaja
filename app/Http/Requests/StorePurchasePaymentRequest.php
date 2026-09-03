<?php

namespace App\Http\Requests;

use App\Models\Purchase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePurchasePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Purchase $purchase */
        $purchase = $this->route('purchase');

        return $this->user()->can('update', $purchase);
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Purchase $purchase */
            $purchase = $this->route('purchase');
            $amount = (float) $this->input('amount');

            if ($amount > $purchase->remaining) {
                $validator->errors()->add(
                    'amount',
                    'Jumlah pembayaran melebihi sisa yang harus dibayar ke supplier ini.'
                );
            }
        });
    }
}
