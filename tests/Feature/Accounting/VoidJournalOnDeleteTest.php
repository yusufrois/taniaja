<?php

namespace Tests\Feature\Accounting;

use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\Debt;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\JournalEntry;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Accounting\AccountingReportService;
use App\Services\Accounting\StandardChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bug report #7 — "saat salah 1 saya hapus saldo tidak kembali dan
 * laporan beban juga tetap seperti sebelum terhapus, tapi menu
 * dashboard sudah berkurang". Dashboard sums Expense::sum() (a plain
 * Eloquent query, correctly excludes soft-deleted rows automatically)
 * — but Laporan/Neraca Saldo reads from the JOURNAL, which never got
 * told the Expense was gone. This test proves both sides now agree
 * after a delete.
 */
class VoidJournalOnDeleteTest extends TestCase
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

    public function test_deleting_an_expense_voids_its_journal_entry_and_removes_it_from_trial_balance(): void
    {
        $company = Company::factory()->create();
        app(StandardChartOfAccountsSeeder::class)->seedFor($company);
        $owner = $this->makeUserWithRole($company, 'owner', ['expense.view', 'expense.create', 'expense.delete', 'accounting.view']);
        $category = ExpenseCategory::create(['company_id' => $company->id, 'name' => 'Pupuk']);

        $keep = Expense::create([
            'company_id' => $company->id, 'expense_category_id' => $category->id,
            'date' => now()->toDateString(), 'amount' => 400000,
        ]);
        $toDelete = Expense::create([
            'company_id' => $company->id, 'expense_category_id' => $category->id,
            'date' => now()->toDateString(), 'amount' => 600000,
        ]);

        // Before delete: both posted, trial balance shows 1.000.000.
        $reportService = app(AccountingReportService::class);
        $this->assertEquals(1000000.0, $reportService->trialBalance($company->id)['total_debit']);

        // Delete the second one.
        $this->actingAs($owner)->deleteJson("/api/v1/expenses/{$toDelete->id}")->assertOk();

        // Journal entry for the deleted Expense is voided (soft-deleted).
        $this->assertSoftDeleted('journal_entries', ['reference_type' => 'expense', 'reference_id' => $toDelete->id]);

        // Trial balance now correctly reflects ONLY the remaining Expense.
        $this->assertEquals(400000.0, $reportService->trialBalance($company->id)['total_debit']);
    }

    public function test_deleting_a_debt_voids_its_journal_entry(): void
    {
        $company = Company::factory()->create();
        app(StandardChartOfAccountsSeeder::class)->seedFor($company);
        $owner = $this->makeUserWithRole($company, 'owner', ['debt.view', 'debt.delete']);

        $debt = Debt::create([
            'company_id' => $company->id, 'creditor_name' => 'Bank BRI',
            'debt_date' => now()->toDateString(), 'amount' => 5000000,
        ]);

        $this->assertDatabaseHas('journal_entries', ['reference_type' => 'debt', 'reference_id' => $debt->id]);

        $this->actingAs($owner)->deleteJson("/api/v1/debts/{$debt->id}")->assertOk();

        $this->assertSoftDeleted('journal_entries', ['reference_type' => 'debt', 'reference_id' => $debt->id]);
    }

    /** Deleting a transaction that NEVER got a journal (e.g. recorded before Chart of Accounts existed) must not error. */
    public function test_deleting_a_transaction_with_no_journal_entry_does_not_error(): void
    {
        $company = Company::factory()->create(); // no Chart of Accounts seeded
        $owner = $this->makeUserWithRole($company, 'owner', ['expense.view', 'expense.create', 'expense.delete']);
        $category = ExpenseCategory::create(['company_id' => $company->id, 'name' => 'Pupuk']);

        $expense = Expense::create([
            'company_id' => $company->id, 'expense_category_id' => $category->id,
            'date' => now()->toDateString(), 'amount' => 100000,
        ]);
        $this->assertDatabaseCount('journal_entries', 0); // confirmed: never posted

        $this->actingAs($owner)->deleteJson("/api/v1/expenses/{$expense->id}")->assertOk();

        $this->assertSoftDeleted('expenses', ['id' => $expense->id]);
    }
}
