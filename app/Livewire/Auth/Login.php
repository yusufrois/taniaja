<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class Login extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    /**
     * Mirrors AuthController::login()'s rules exactly (required/email,
     * required password, active-status check) — this is the session-based
     * web equivalent of that API endpoint, not a separate auth system.
     */
    public function login()
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            $this->addError('email', 'Email atau password salah.');

            return;
        }

        if (Auth::user()->status !== 'active') {
            Auth::logout();
            $this->addError('email', 'Akun tidak aktif.');

            return;
        }

        // NOTE: session() (the global helper) is used here, not
        // request()->session() — inside a Livewire component (especially
        // under Livewire::test()), the request object isn't always bound
        // to a session store the same way a classic controller request
        // is, which throws "Session store not set on request." The
        // session() helper resolves the session manager directly from
        // the container instead, which works correctly in both contexts.
        session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
