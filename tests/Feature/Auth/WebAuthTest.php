<?php

namespace Tests\Feature\Auth;

use App\Livewire\Auth\Login;
use App\Livewire\Auth\RegisterCompany;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class WebAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_visiting_dashboard_is_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_login_page_loads_for_a_guest(): void
    {
        $response = $this->get('/login');
        $response->assertOk();
        $response->assertSeeLivewire(Login::class);
    }

    public function test_user_can_log_in_with_valid_credentials(): void
    {
        $company = Company::factory()->create();
        $role = Role::create(['company_id' => $company->id, 'slug' => 'owner', 'name' => 'Owner']);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);
        $user->roles()->attach($role->id);

        Livewire::test(Login::class)
            ->set('email', 'test@example.com')
            ->set('password', 'password123')
            ->call('login')
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $company = Company::factory()->create();
        User::factory()->create([
            'company_id' => $company->id,
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'status' => 'active',
        ]);

        Livewire::test(Login::class)
            ->set('email', 'test@example.com')
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $company = Company::factory()->create();
        User::factory()->create([
            'company_id' => $company->id,
            'email' => 'inactive@example.com',
            'password' => Hash::make('password123'),
            'status' => 'inactive',
        ]);

        Livewire::test(Login::class)
            ->set('email', 'inactive@example.com')
            ->set('password', 'password123')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_registering_a_new_company_logs_the_owner_in_and_creates_the_role(): void
    {
        Livewire::test(RegisterCompany::class)
            ->set('company_name', 'Kebun Makmur')
            ->set('company_code', 'KMR')
            ->set('owner_name', 'Budi')
            ->set('owner_email', 'budi@kebunmakmur.test')
            ->set('owner_password', 'password123')
            ->set('owner_password_confirmation', 'password123')
            ->call('register')
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('companies', ['code' => 'KMR', 'name' => 'Kebun Makmur']);

        $user = User::where('email', 'budi@kebunmakmur.test')->first();
        $this->assertNotNull($user);
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasRole('owner'));
    }

    public function test_registration_rejects_duplicate_company_code(): void
    {
        Company::factory()->create(['code' => 'DUP']);

        Livewire::test(RegisterCompany::class)
            ->set('company_name', 'Perusahaan Lain')
            ->set('company_code', 'DUP') // already taken
            ->set('owner_name', 'Siti')
            ->set('owner_email', 'siti@lain.test')
            ->set('owner_password', 'password123')
            ->set('owner_password_confirmation', 'password123')
            ->call('register')
            ->assertHasErrors('company_code');

        $this->assertGuest();
    }

    public function test_authenticated_user_is_redirected_away_from_login_page(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->get('/login');

        // Only asserting a redirect happened (302), not the exact target —
        // Laravel's built-in 'guest' middleware redirect destination can
        // vary depending on framework version/config, and asserting an
        // unverified exact path here would risk a false failure.
        $response->assertStatus(302);
    }
}
