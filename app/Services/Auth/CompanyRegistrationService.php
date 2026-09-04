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
 *
 * Bug fix history on this file — read before touching it again:
 * 1. (Fase L1) Added automatic Chart of Accounts seeding on register.
 * 2. (Roadmap tambahan) Fixed "untuk peran cuma ada company owner
 *    aja" — this used to ONLY create the 'owner' Role with NO
 *    permissions attached at all (RoleCapabilitySeeder can't help;
 *    it only attaches permissions to roles that already exist, never
 *    creates them). Now delegates to RoleMatrixService, which creates
 *    all 5 standard roles with correct permissions in one place.
 * 3. That same role-provisioning fix ACCIDENTALLY dropped fix #1's
 *    Chart of Accounts seeding when this file was rewritten from an
 *    older copy that predated it — caught by
 *    AccountingTest::new_company_registration_automatically_seeds_standard_chart_of_accounts.
 *    Both fixes now live here together; if this file is ever
 *    rewritten wholesale again, check for BOTH before assuming a
 *    "clean" version is actually the latest one.
 */
class CompanyRegistrationService
{
    public function __construct(
        private RoleMatrixService $roleMatrix,
        private StandardChartOfAccountsSeeder $coaSeeder,
    ) {}

    public function register(array $data): User
    {
        $company = Company::create([
            'name' => $data['company_name'],
            'code' => $data['company_code'],
            'status' => 'active',
        ]);

        // Every new company starts with the standard Chart of
        // Accounts already in place, so accounting features work
        // immediately without a separate manual setup step.
        $this->coaSeeder->seedFor($company);

        $this->roleMatrix->provisionRolesForCompany($company->id);

        $ownerRole = Role::where('company_id', $company->id)->where('slug', 'owner')->firstOrFail();

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
