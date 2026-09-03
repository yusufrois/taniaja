<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\ResetPassword;
use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_sends_reset_link_for_existing_user(): void
    {
        // Notification::fake() intercepts the reset-link email so this
        // test never actually attempts to send mail — safe regardless
        // of the MAIL_MAILER configured in .env on whoever's machine
        // runs this test.
        Notification::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id, 'email' => 'test@example.com']);

        Livewire::test(ForgotPassword::class)
            ->set('email', 'test@example.com')
            ->call('sendResetLink')
            ->assertHasNoErrors();

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_forgot_password_shows_error_for_unknown_email(): void
    {
        Livewire::test(ForgotPassword::class)
            ->set('email', 'tidak-terdaftar@example.com')
            ->call('sendResetLink')
            ->assertHasErrors('email');
    }

    public function test_user_can_reset_password_with_valid_token_and_log_in_with_new_password(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'reset@example.com',
            'password' => Hash::make('old-password'),
        ]);

        $token = Password::createToken($user);

        Livewire::test(ResetPassword::class, ['token' => $token])
            ->set('email', 'reset@example.com')
            ->set('password', 'new-password-123')
            ->set('password_confirmation', 'new-password-123')
            ->call('resetPassword')
            ->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));

        // Old password must no longer work.
        Livewire::test(\App\Livewire\Auth\Login::class)
            ->set('email', 'reset@example.com')
            ->set('password', 'old-password')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_reset_password_fails_with_invalid_token(): void
    {
        $company = Company::factory()->create();
        User::factory()->create(['company_id' => $company->id, 'email' => 'reset2@example.com']);

        Livewire::test(ResetPassword::class, ['token' => 'token-yang-salah'])
            ->set('email', 'reset2@example.com')
            ->set('password', 'new-password-123')
            ->set('password_confirmation', 'new-password-123')
            ->call('resetPassword')
            ->assertHasErrors('email');
    }

    public function test_reset_password_requires_matching_confirmation(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id, 'email' => 'reset3@example.com']);
        $token = Password::createToken($user);

        Livewire::test(ResetPassword::class, ['token' => $token])
            ->set('email', 'reset3@example.com')
            ->set('password', 'new-password-123')
            ->set('password_confirmation', 'tidak-cocok')
            ->call('resetPassword')
            ->assertHasErrors('password');
    }
}
