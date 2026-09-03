<?php

namespace Tests\Feature\Accounting;

use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingTest extends TestCase
{
    use RefreshDatabase;

    private function makeUserWithRole(Company $company, string $slug, array $permissionNames = []): User
    {
        $role = Role::create(['company_id' => $company->id, 'slug' => $slug, 'name' => ucfirst($slug)]);

        if ($permissionNames) {
            $ids = collect($permissionNames)->map(
                fn ($name) => Permission::firstOrCreate(['name' => $name], ['module' => explode('.', $name)[0]])->id
            );
            $role->permissions()->sync($ids);
        }

        $user = User::factory()->create(['company_id' => $company->id]);
        $user->roles()->attach($role->id);

        return $user;
    }

    public function test_new_company_registration_automatically_seeds_standard_chart_of_accounts(): void
    {
        $response = $this->postJson('/api/v1/register-company', [
            'company_name' => 'Kebun Baru',
            'company_code' => 'KBB',
            'owner_name' => 'Owner Baru',
            'owner_email' => 'ownerbaru@test.test',
            'owner_password' => 'password123',
        ]);

        $response->assertCreated();

        $companyId = \App\Models\Company::where('code', 'KBB')->first()->id;

        $this->assertEquals(16, ChartOfAccount::where('company_id', $companyId)->count());
        $this->assertDatabaseHas('chart_of_accounts', ['company_id' => $companyId, 'code' => '1100', 'name' => 'Kas']);
        $this->assertDatabaseHas('chart_of_accounts', ['company_id' => $companyId, 'code' => '4100', 'name' => 'Pendapatan Penjualan']);
    }

    public function test_owner_can_seed_defaults_for_an_existing_company_without_duplicating(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['accounting.create']);

        $first = $this->actingAs($owner)->postJson('/api/v1/chart-of-accounts/seed-defaults');
        $first->assertOk();
        $this->assertCount(16, $first->json('data'));

        // Calling again must NOT create duplicates (idempotent).
        $second = $this->actingAs($owner)->postJson('/api/v1/chart-of-accounts/seed-defaults');
        $second->assertOk();
        $this->assertEquals(16, ChartOfAccount::where('company_id', $company->id)->count());
    }

    public function test_balanced_journal_entry_is_accepted(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['accounting.create']);
        $kas = ChartOfAccount::create(['company_id' => $company->id, 'code' => '1100', 'name' => 'Kas', 'type' => 'asset']);
        $modal = ChartOfAccount::create(['company_id' => $company->id, 'code' => '3100', 'name' => 'Modal Pemilik', 'type' => 'equity']);

        // Owner injects capital: Debit Kas, Kredit Modal — Rp5.000.000.
        $response = $this->actingAs($owner)->postJson('/api/v1/journal-entries', [
            'date' => now()->toDateString(),
            'description' => 'Setoran modal awal',
            'lines' => [
                ['chart_of_account_id' => $kas->id, 'debit' => 5000000, 'credit' => 0],
                ['chart_of_account_id' => $modal->id, 'debit' => 0, 'credit' => 5000000],
            ],
        ]);

        $response->assertCreated();
        $this->assertCount(2, $response->json('data.lines'));
    }

    public function test_unbalanced_journal_entry_is_rejected(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['accounting.create']);
        $kas = ChartOfAccount::create(['company_id' => $company->id, 'code' => '1100', 'name' => 'Kas', 'type' => 'asset']);
        $modal = ChartOfAccount::create(['company_id' => $company->id, 'code' => '3100', 'name' => 'Modal Pemilik', 'type' => 'equity']);

        $response = $this->actingAs($owner)->postJson('/api/v1/journal-entries', [
            'date' => now()->toDateString(),
            'description' => 'Jurnal tidak seimbang',
            'lines' => [
                ['chart_of_account_id' => $kas->id, 'debit' => 5000000, 'credit' => 0],
                ['chart_of_account_id' => $modal->id, 'debit' => 0, 'credit' => 3000000], // beda jumlah
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('lines');
        $this->assertDatabaseCount('journal_entries', 0); // nothing was created
    }

    public function test_journal_line_cannot_have_both_debit_and_credit(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['accounting.create']);
        $kas = ChartOfAccount::create(['company_id' => $company->id, 'code' => '1100', 'name' => 'Kas', 'type' => 'asset']);
        $modal = ChartOfAccount::create(['company_id' => $company->id, 'code' => '3100', 'name' => 'Modal Pemilik', 'type' => 'equity']);

        $response = $this->actingAs($owner)->postJson('/api/v1/journal-entries', [
            'date' => now()->toDateString(),
            'description' => 'Baris salah',
            'lines' => [
                ['chart_of_account_id' => $kas->id, 'debit' => 1000, 'credit' => 1000], // keduanya diisi
                ['chart_of_account_id' => $modal->id, 'debit' => 0, 'credit' => 1000],
            ],
        ]);

        $response->assertStatus(422);
    }

    /**
     * The core double-entry payoff: an account's balance is correctly
     * derived from posted journal lines, not stored/manually tracked.
     */
    public function test_account_balance_reflects_posted_journal_entries(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['accounting.create', 'accounting.view', 'cost.view']);
        $kas = ChartOfAccount::create(['company_id' => $company->id, 'code' => '1100', 'name' => 'Kas', 'type' => 'asset']);
        $modal = ChartOfAccount::create(['company_id' => $company->id, 'code' => '3100', 'name' => 'Modal Pemilik', 'type' => 'equity']);

        $this->actingAs($owner)->postJson('/api/v1/journal-entries', [
            'date' => now()->toDateString(), 'description' => 'Modal awal',
            'lines' => [
                ['chart_of_account_id' => $kas->id, 'debit' => 5000000, 'credit' => 0],
                ['chart_of_account_id' => $modal->id, 'debit' => 0, 'credit' => 5000000],
            ],
        ])->assertCreated();

        $show = $this->actingAs($owner)->getJson("/api/v1/chart-of-accounts/{$kas->id}");
        $show->assertOk();
        // Kas is an ASSET — Debit increases it.
        $this->assertEquals(5000000.0, $show->json('data.balance'));

        $modalShow = $this->actingAs($owner)->getJson("/api/v1/chart-of-accounts/{$modal->id}");
        // Modal is EQUITY — Credit increases it.
        $this->assertEquals(5000000.0, $modalShow->json('data.balance'));
    }

    /**
     * Voiding a journal entry must actually remove its effect from
     * account balances, not just hide it from the list.
     */
    public function test_voiding_a_journal_entry_removes_it_from_account_balance(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', [
            'accounting.create', 'accounting.view', 'accounting.delete', 'cost.view',
        ]);
        $kas = ChartOfAccount::create(['company_id' => $company->id, 'code' => '1100', 'name' => 'Kas', 'type' => 'asset']);
        $modal = ChartOfAccount::create(['company_id' => $company->id, 'code' => '3100', 'name' => 'Modal Pemilik', 'type' => 'equity']);

        $entry = $this->actingAs($owner)->postJson('/api/v1/journal-entries', [
            'date' => now()->toDateString(), 'description' => 'Modal awal',
            'lines' => [
                ['chart_of_account_id' => $kas->id, 'debit' => 5000000, 'credit' => 0],
                ['chart_of_account_id' => $modal->id, 'debit' => 0, 'credit' => 5000000],
            ],
        ]);
        $entryId = $entry->json('data.id');

        $this->actingAs($owner)->deleteJson("/api/v1/journal-entries/{$entryId}")->assertOk();

        $show = $this->actingAs($owner)->getJson("/api/v1/chart-of-accounts/{$kas->id}");
        $this->assertEquals(0.0, $show->json('data.balance')); // back to zero, entry voided
    }

    public function test_supervisor_cannot_access_accounting(): void
    {
        $company = Company::factory()->create();
        $supervisor = $this->makeUserWithRole($company, 'supervisor', []); // no accounting permission by default

        $response = $this->actingAs($supervisor)->getJson('/api/v1/chart-of-accounts');
        $response->assertForbidden();
    }
}
