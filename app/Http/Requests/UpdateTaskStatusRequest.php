<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Lighter than UpdateTaskRequest — see TaskPolicy::view() vs
        // update(): the assignee can progress their own task's status
        // without needing full edit rights over title/description.
        return $this->user()->can('view', $this->route('task'));
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['pending', 'in_progress', 'completed'])],
        ];
    }
}
