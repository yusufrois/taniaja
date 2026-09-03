<?php

namespace App\Http\Requests;

use App\Models\StockBatchSale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockBatchSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', StockBatchSale::class);
    }

    public function rules(): array
    {
        return [
            'customer_id' => [
                'nullable',
                Rule::exists('customers', 'id')->where('company_id', $this->user()->company_id),
            ],
            'quantity_sold' => ['required', 'numeric', 'min:0.01'],
            'sale_price_per_unit' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
