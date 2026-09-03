<?php

namespace App\Http\Requests;

use App\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSalePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Sale $sale */
        $sale = $this->route('sale');

        return $this->user()->can('update', $sale);
    }

    public function rules(): array
    {
        return [
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            // Roadmap Fase L5 — which Kas/Bank account received this
            // payment. Optional: falls back to the default Kas (1100)
            // if omitted, see AccountingPostingService.
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

    // Same overpayment guard as StoreDebtPaymentRequest (Phase 5) and
    // StorePurchasePaymentRequest (Phase 6) — a customer's payment can
    // never exceed what they still owe on this invoice.
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Sale $sale */
            $sale = $this->route('sale');
            $amount = (float) $this->input('amount');

            if ($amount > $sale->remaining) {
                $validator->errors()->add(
                    'amount',
                    'Jumlah pembayaran melebihi sisa tagihan invoice ini.'
                );
            }
        });
    }
}
