<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Task::class);
    }

    public function rules(): array
    {
        $companyId = $this->user()->company_id;

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assigned_to' => ['required', Rule::exists('users', 'id')->where('company_id', $companyId)],
            'due_date' => ['nullable', 'date'],
            // Optional initial checklist — "1 tugas bisa punya beberapa
            // sub-langkah" confirmed in discussion.
            'steps' => ['nullable', 'array'],
            'steps.*' => ['required', 'string', 'max:255'],
        ];
    }
}
