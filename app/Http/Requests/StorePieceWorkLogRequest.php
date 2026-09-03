<?php

namespace App\Http\Requests;

use App\Models\PieceWorkLog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePieceWorkLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', PieceWorkLog::class);
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('company_id', $companyId)],
            'work_type_id' => ['required', Rule::exists('work_types', 'id')->where('company_id', $companyId)],
            'date' => ['required', 'date'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
