<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Debt\Manage;
use App\Models\Company;
use App\Models\Debt;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Roadmap tambahan (permintaan pengguna) — jadwal cicilan bulanan
 * otomatis, penanda "cicilan ke berapa", dan tampilan riwayat semua
 * pembayaran.
 */
class DebtInstallmentTest extends TestCase
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

    public function test_owner_can_record_a_debt_with_24_month_installment_plan(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['debt.view', 'debt.create']);

        Livewire::actingAs($owner)
            ->test(Manage::class)
            ->call('openCreate')
            ->set('creditor_name', 'Bank BRI')
            ->set('debt_date', now()->toDateString())
            ->set('amount', 24000000)
            ->set('installment_months', 24)
            ->call('save')
            ->assertHasNoErrors();

        $debt = Debt::where('creditor_name', 'Bank BRI')->first();
        $this->assertEquals(24, $debt->installment_months);
        $this->assertEquals(1000000.0, $debt->installment_amount); // 24jt / 24 bulan
    }

    /** The generated schedule has the right number of entries, dates a month apart, and the right per-installment amount. */
    public function test_schedule_generates_correct_monthly_due_dates(): void
    {
        $company = Company::factory()->create();
        $debt = Debt::create([
            'company_id' => $company->id, 'creditor_name' => 'Bank BRI',
            'debt_date' => '2026-01-15', 'amount' => 12000000, 'installment_months' => 12,
        ]);

        $schedule = $debt->schedule();

        $this->assertCount(12, $schedule);
        $this->assertEquals('2026-02-15', $schedule->first()['due_date']->toDateString());
        $this->assertEquals('2027-01-15', $schedule->last()['due_date']->toDateString());
        $this->assertEquals(1000000.0, $schedule->first()['amount']);
    }

    /**
     * "Penanda cicilan ke berapa" — cumulative payments correctly mark
     * how many installments are covered so far.
     */
    public function test_installments_paid_counter_reflects_cumulative_payments(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['debt.view', 'debt.update']);
        $debt = Debt::create([
            'company_id' => $company->id, 'creditor_name' => 'Bank BRI',
            'debt_date' => now()->toDateString(), 'amount' => 10000000, 'installment_months' => 10,
        ]);

        // Pay enough to cover exactly 3 installments (3 x 1.000.000).
        Livewire::actingAs($owner)
            ->test(Manage::class)
            ->call('openPayment', $debt->id)
            ->set('payment_date', now()->toDateString())
            ->set('payment_amount', 3000000)
            ->call('savePayment')
            ->assertHasNoErrors();

        $this->assertEquals(3, $debt->fresh()->installments_paid);

        $schedule = $debt->fresh()->schedule();
        $this->assertTrue($schedule[0]['is_paid']); // bulan 1
        $this->assertTrue($schedule[2]['is_paid']); // bulan 3
        $this->assertFalse($schedule[3]['is_paid']); // bulan 4 belum
    }

    /** A debt WITHOUT an installment plan is unaffected — empty schedule, null installment fields. */
    public function test_debt_without_installment_plan_has_empty_schedule(): void
    {
        $company = Company::factory()->create();
        $debt = Debt::create([
            'company_id' => $company->id, 'creditor_name' => 'Koperasi Tani',
            'debt_date' => now()->toDateString(), 'amount' => 5000000, // no installment_months
        ]);

        $this->assertNull($debt->installment_amount);
        $this->assertNull($debt->installments_paid);
        $this->assertCount(0, $debt->schedule());
    }

    /** "Riwayat semua pembayaran" — the detail modal correctly loads payment history. */
    public function test_owner_can_open_debt_detail_showing_payment_history(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['debt.view', 'debt.update', 'cost.view']);
        $debt = Debt::create([
            'company_id' => $company->id, 'creditor_name' => 'Bank BRI',
            'debt_date' => now()->toDateString(), 'amount' => 5000000, 'installment_months' => 5,
        ]);
        $debt->payments()->create(['company_id' => $company->id, 'payment_date' => now()->toDateString(), 'amount' => 1000000]);

        $component = Livewire::actingAs($owner)
            ->test(Manage::class)
            ->call('openDetail', $debt->id)
            ->assertSet('showDetailModal', true);

        // ->get() only reads PUBLIC PROPERTIES, not extra view data —
        // check the actual rendered HTML shows the payment instead.
        $component->assertSee('1.000.000');
    }
}
