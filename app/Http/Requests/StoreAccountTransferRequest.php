<?php

namespace App\Http\Requests;

use App\Models\AccountTransfer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAccountTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', AccountTransfer::class);
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [
            'from_account_id' => [
                'required',
                Rule::exists('chart_of_accounts', 'id')->where('company_id', $companyId)->where('type', 'asset'),
            ],
            'to_account_id' => [
                'required',
                Rule::exists('chart_of_accounts', 'id')->where('company_id', $companyId)->where('type', 'asset'),
            ],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('from_account_id') === $this->input('to_account_id')) {
                $validator->errors()->add('to_account_id', 'Akun tujuan tidak boleh sama dengan akun asal.');
            }
        });
    }
}
