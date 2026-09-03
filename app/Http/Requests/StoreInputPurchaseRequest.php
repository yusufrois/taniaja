<?php

namespace App\Http\Requests;

use App\Models\InputPurchase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInputPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', InputPurchase::class);
    }

    /**
     * No longer needs expense_category_id — a purchase (buying stock)
     * no longer creates an Expense directly. Cost is recognized later,
     * at usage time, against InputItem.default_expense_category_id.
     * See InputUsageController::store().
     */
    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [
            'input_item_id' => ['required', Rule::exists('input_items', 'id')->where('company_id', $companyId)],
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->where('company_id', $companyId)],
            'greenhouse_id' => ['nullable', Rule::exists('greenhouses', 'id')->where('company_id', $companyId)],
            'season_id' => ['nullable', Rule::exists('seasons', 'id')->where('company_id', $companyId)],
            'purchase_date' => ['required', 'date'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
