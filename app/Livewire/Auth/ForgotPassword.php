<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class ForgotPassword extends Component
{
    public string $email = '';
    public ?string $status = null;

    /**
     * Uses Laravel's built-in password broker (Password::sendResetLink)
     * — the `password_reset_tokens` table already exists by default
     * from `laravel new` (Laravel 11/12's base migration), and User
     * already implements CanResetPassword via the framework's own
     * Illuminate\Foundation\Auth\User base class we extend, so no new
     * migration or model changes are needed for this feature.
     */
    public function sendResetLink(): void
    {
        $this->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink(['email' => $this->email]);

        if ($status === Password::RESET_LINK_SENT) {
            $this->status = __($status);
        } else {
            $this->addError('email', __($status));
        }
    }

    public function render()
    {
        return view('livewire.auth.forgot-password');
    }
}
