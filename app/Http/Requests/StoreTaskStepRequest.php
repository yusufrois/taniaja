<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTaskStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Adding a NEW checklist item is an editorial change — same
        // bar as UpdateTaskRequest (assigner or task.update), not the
        // lighter "just checking things off" bar. See TaskPolicy.
        return $this->user()->can('update', $this->route('task'));
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:255'],
        ];
    }
}
