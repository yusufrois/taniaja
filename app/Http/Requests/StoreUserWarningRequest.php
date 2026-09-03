<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserWarningRequest extends FormRequest
{
    public function authorize(): bool
    {
        // 'user.warn' is deliberately its own permission, separate from
        // 'user.update' — an Owner might want to let a Manager issue
        // warnings to their team without also granting full user
        // management (create/edit/suspend) rights.
        return $this->user()->hasPermission('user.warn');
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
