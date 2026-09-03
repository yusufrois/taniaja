<?php

namespace App\Http\Requests;

use App\Models\ActivityTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActivityTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ActivityTemplate::class);
    }

    public function rules(): array
    {
        return [
            'variety_id' => [
                'required',
                Rule::exists('varieties', 'id')->where('company_id', $this->user()->company_id),
            ],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
