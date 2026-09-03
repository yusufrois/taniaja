<?php

namespace App\Http\Requests;

use App\Models\Harvest;
use App\Models\Season;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHarvestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Harvest::class);
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [
            'season_id' => ['required', Rule::exists('seasons', 'id')->where('company_id', $companyId)],
            'harvest_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.grade_id' => ['required', Rule::exists('grades', 'id')->where('company_id', $companyId)],
            'items.*.weight' => ['required', 'numeric', 'min:0.01'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.quality_notes' => ['nullable', 'string'],
        ];
    }
}
