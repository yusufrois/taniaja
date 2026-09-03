<?php

namespace App\Http\Requests;

use App\Models\InputItem;
use App\Models\InputUsage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreInputUsageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', InputUsage::class);
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [
            'input_item_id' => ['required', Rule::exists('input_items', 'id')->where('company_id', $companyId)],
            'greenhouse_id' => ['nullable', Rule::exists('greenhouses', 'id')->where('company_id', $companyId)],
            'season_id' => ['nullable', Rule::exists('seasons', 'id')->where('company_id', $companyId)],
            'activity_id' => ['nullable', Rule::exists('activities', 'id')->where('company_id', $companyId)],
            'used_date' => ['required', 'date'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Same "can't consume more than what's available" principle used
     * throughout this app (debt/purchase/sale payments, stock batch
     * sales, plant loss) — here applied to fertilizer/input stock.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $inputItemId = $this->input('input_item_id');
            $quantity = (float) $this->input('quantity');

            if (! $inputItemId || $quantity <= 0) {
                return; // already caught by the basic rules above
            }

            $inputItem = InputItem::find($inputItemId);

            if (! $inputItem) {
                return;
            }

            $available = $inputItem->currentStock();

            if ($quantity > $available) {
                $validator->errors()->add(
                    'quantity',
                    "Jumlah yang dipakai ({$quantity} {$inputItem->unit}) melebihi stok tersedia ".
                    "({$available} {$inputItem->unit})."
                );
            }
        });
    }
}
