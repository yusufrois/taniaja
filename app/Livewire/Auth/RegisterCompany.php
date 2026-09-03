<?php

namespace App\Livewire\Auth;

use App\Services\Auth\CompanyRegistrationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class RegisterCompany extends Component
{
    public string $company_name = '';
    public string $company_code = '';
    public string $owner_name = '';
    public string $owner_email = '';
    public string $owner_password = '';
    public string $owner_password_confirmation = '';

    /**
     * Same validation rules as AuthController::registerCompany() (API),
     * plus 'confirmed' since a web form has a visible password-confirm
     * field that the API doesn't need. After registering, this logs the
     * new Owner straight into a web session (Auth::login) instead of
     * issuing a Sanctum token — that's the only difference in what
     * happens AFTER CompanyRegistrationService does its shared work.
     */
    public function register(CompanyRegistrationService $registrationService)
    {
        $validated = $this->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'company_code' => ['required', 'string', 'max:50', 'unique:companies,code'],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email', 'unique:users,email'],
            'owner_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $registrationService->register($validated);

        Auth::login($user);
        // See the note in Login.php — session() helper, not
        // request()->session(), for the same reason.
        session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function render()
    {
        return view('livewire.auth.register-company');
    }
}
