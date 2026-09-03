<?php

namespace App\Services\Auth;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Services\Accounting\StandardChartOfAccountsSeeder;
use Illuminate\Support\Facades\Hash;

/**
 * Single source of truth for "register a brand-new tenant Company with
 * its first user as Owner" — used by BOTH the API (AuthController, for
 * Postman/Flutter/third-party clients) and the web UI (Livewire
 * RegisterCompany component, introduced in Phase 9), so the two never
 * drift apart from each other. Whichever caller needs a Sanctum token
 * or a web session afterward handles that itself; this service only
 * does the tenant-creation part both flows share identically.
 */
class CompanyRegistrationService
{
    public function __construct(private StandardChartOfAccountsSeeder $coaSeeder) {}

    public function register(array $data): User
    {
        $company = Company::create([
            'name' => $data['company_name'],
            'code' => $data['company_code'],
            'status' => 'active',
        ]);

        // Roadmap Fase L1 — every new company starts with the standard
        // Chart of Accounts already in place, so accounting features
        // work immediately without a separate manual setup step.
        $this->coaSeeder->seedFor($company);

        $ownerRole = Role::firstOrCreate(
            ['company_id' => $company->id, 'slug' => 'owner'],
            ['name' => 'Company Owner', 'is_system' => true]
        );

        $user = User::create([
            'company_id' => $company->id,
            'name' => $data['owner_name'],
            'email' => $data['owner_email'],
            'password' => Hash::make($data['owner_password']),
            'status' => 'active',
        ]);

        $user->roles()->attach($ownerRole->id);

        AuditLog::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'action' => 'company.registered',
            'model' => Company::class,
            'model_id' => $company->id,
        ]);

        return $user->fresh(['company', 'roles']);
    }
}
