<?php

namespace App\Http\Requests;

use App\Models\Purchase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Purchase::class);
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->where('company_id', $companyId)],
            'crop_id' => ['required', Rule::exists('crops', 'id')->where('company_id', $companyId)],
            'variety_id' => ['nullable', Rule::exists('varieties', 'id')->where('company_id', $companyId)],
            'grade_id' => ['nullable', Rule::exists('grades', 'id')->where('company_id', $companyId)],
            'purchase_date' => ['required', 'date'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
