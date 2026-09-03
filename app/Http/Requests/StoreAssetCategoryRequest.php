<?php

namespace App\Http\Requests;

use App\Models\AssetCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssetCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', AssetCategory::class);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('asset_categories', 'name')->where('company_id', $this->user()->company_id),
            ],
        ];
    }
}
