<?php

namespace Tests\Feature\Livewire;

use App\Livewire\ChartOfAccount\Manage;
use App\Models\Company;
use App\Models\Debt;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\JournalEntry;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Roadmap tambahan — real-world gap the person actually hit: Beban
 * and Hutang recorded BEFORE the Chart of Accounts existed never got
 * a journal entry (posting silently failed, by design, so the
 * transaction itself still succeeded) — leaving Laporan empty even
 * though the data is genuinely there.
 */
class AccountingBackfillTest extends TestCase
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

    /**
     * The exact scenario reported: Expense + Debt recorded with NO
     * Chart of Accounts yet (posting silently fails), THEN accounts
     * get seeded, THEN backfill should retroactively create the
     * missing journal entries.
     */
    public function test_backfill_posts_journal_entries_for_transactions_recorded_before_chart_of_accounts_existed(): void
    {
        $company = Company::factory()->create(); // deliberately NO Chart of Accounts seeded yet
        $owner = $this->makeUserWithRole($company, 'owner', ['accounting.view', 'accounting.create']);
        $category = ExpenseCategory::create(['company_id' => $company->id, 'name' => 'Pupuk']);

        // Recorded while Chart of Accounts didn't exist — succeeds, but no journal entry.
        $expense = Expense::create([
            'company_id' => $company->id, 'expense_category_id' => $category->id,
            'date' => now()->toDateString(), 'amount' => 500000, 'approved_at' => now(),
        ]);
        $debt = Debt::create([
            'company_id' => $company->id, 'creditor_name' => 'Bank BRI',
            'debt_date' => now()->toDateString(), 'amount' => 10000000,
        ]);

        $this->assertDatabaseCount('journal_entries', 0); // confirmed: nothing posted yet

        // NOW set up the Chart of Accounts (what the person did after discovering the missing menu).
        Livewire::actingAs($owner)->test(Manage::class)->call('seedDefaults');

        // Backfill should retroactively post both.
        Livewire::actingAs($owner)
            ->test(Manage::class)
            ->call('backfillOldTransactions')
            ->assertSet('backfillResult.expense', 1)
            ->assertSet('backfillResult.debt', 1);

        $this->assertTrue(JournalEntry::where('reference_type', 'expense')->where('reference_id', $expense->id)->exists());
        $this->assertTrue(JournalEntry::where('reference_type', 'debt')->where('reference_id', $debt->id)->exists());
    }

    /** Running backfill twice must NOT create duplicate journal entries. */
    public function test_backfill_is_idempotent(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['accounting.view', 'accounting.create']);
        $category = ExpenseCategory::create(['company_id' => $company->id, 'name' => 'Pupuk']);
        Expense::create([
            'company_id' => $company->id, 'expense_category_id' => $category->id,
            'date' => now()->toDateString(), 'amount' => 200000, 'approved_at' => now(),
        ]);

        Livewire::actingAs($owner)->test(Manage::class)->call('seedDefaults');

        Livewire::actingAs($owner)->test(Manage::class)->call('backfillOldTransactions');
        $this->assertEquals(1, JournalEntry::where('reference_type', 'expense')->count());

        // Second run: nothing left to backfill, no duplicate.
        Livewire::actingAs($owner)
            ->test(Manage::class)
            ->call('backfillOldTransactions')
            ->assertSet('backfillResult.expense', 0);
        $this->assertEquals(1, JournalEntry::where('reference_type', 'expense')->count());
    }

    /**
     * Ties back to the original complaint: after backfilling, the
     * Laporan page (Neraca Saldo) actually reflects the old data.
     */
    public function test_laporan_reflects_backfilled_transactions(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['accounting.view', 'accounting.create']);
        $category = ExpenseCategory::create(['company_id' => $company->id, 'name' => 'Pupuk']);
        Expense::create([
            'company_id' => $company->id, 'expense_category_id' => $category->id,
            'date' => now()->toDateString(), 'amount' => 750000, 'approved_at' => now(),
        ]);

        Livewire::actingAs($owner)->test(Manage::class)->call('seedDefaults');
        Livewire::actingAs($owner)->test(Manage::class)->call('backfillOldTransactions');

        $trialBalance = app(\App\Services\Accounting\AccountingReportService::class)->trialBalance($company->id);
        $this->assertEquals(750000.0, $trialBalance['total_debit']);
    }

    public function test_worker_without_permission_cannot_trigger_backfill(): void
    {
        $company = Company::factory()->create();
        $worker = $this->makeUserWithRole($company, 'worker', []);

        $this->assertTrue($worker->cannot('create', \App\Models\ChartOfAccount::class));
    }
}
