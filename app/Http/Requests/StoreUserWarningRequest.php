<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreUserWarningRequest extends FormRequest
{
    public function authorize(): bool
    {
        // 'user.warn' is deliberately its own permission, separate from
        // 'user.update' — an Owner might want to let a Manager issue
        // warnings to their team without also granting full user
        // management (create/edit/suspend) rights.
        //
        // Roadmap tambahan — ALSO true for whoever is this specific
        // staff member's DIRECT supervisor, even without the blanket
        // 'user.warn' role permission. "Semua atasan yang punya anak
        // buah bisa kirim peringatan ke bawahannya" — account
        // creation stays Owner-only (StoreUserRequest, untouched),
        // this is only about warning/notifying an existing report.
        /** @var User $target */
        $target = $this->route('user');

        return $this->user()->hasPermission('user.warn')
            || $this->user()->isSupervisorOf($target);
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
