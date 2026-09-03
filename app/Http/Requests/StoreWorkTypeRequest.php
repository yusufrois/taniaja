<?php

namespace App\Http\Requests;

use App\Models\WorkType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', WorkType::class);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('work_types', 'name')->where('company_id', $this->user()->company_id),
            ],
            'unit' => ['required', 'string', 'max:50'],
            'rate' => ['required', 'numeric', 'min:0'],
        ];
    }
}
