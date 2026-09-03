<?php

namespace Tests\Feature\Payroll;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollTest extends TestCase
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

    public function test_owner_can_create_a_work_type(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['work_type.create']);

        $response = $this->actingAs($owner)->postJson('/api/v1/work-types', [
            'name' => 'Panen Melon', 'unit' => 'kg', 'rate' => 5000,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('work_types', ['name' => 'Panen Melon', 'rate' => 5000]);
    }

    public function test_recording_piece_work_freezes_rate_and_computes_amount(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['piece_work_log.create']);
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        $workType = WorkType::factory()->create(['company_id' => $company->id, 'rate' => 5000]);

        $response = $this->actingAs($owner)->postJson('/api/v1/piece-work-logs', [
            'employee_id' => $employee->id,
            'work_type_id' => $workType->id,
            'date' => now()->toDateString(),
            'quantity' => 50,
        ]);

        $response->assertCreated();
        $this->assertEquals('5000.00', $response->json('data.rate'));
        $this->assertEquals('250000.00', $response->json('data.amount')); // 50 * 5000
    }

    public function test_piece_work_amount_stays_frozen_even_if_work_type_rate_changes_later(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', [
            'piece_work_log.create', 'piece_work_log.view', 'work_type.update',
        ]);
        $employee = Employee::factory()->create(['company_id' => $company->id]);
        $workType = WorkType::factory()->create(['company_id' => $company->id, 'rate' => 5000]);

        $log = $this->actingAs($owner)->postJson('/api/v1/piece-work-logs', [
            'employee_id' => $employee->id, 'work_type_id' => $workType->id,
            'date' => now()->toDateString(), 'quantity' => 50,
        ]);
        $logId = $log->json('data.id');

        // Rate changes AFTER the entry was logged.
        $this->actingAs($owner)->putJson("/api/v1/work-types/{$workType->id}", ['rate' => 8000]);

        $recheck = $this->actingAs($owner)->getJson("/api/v1/piece-work-logs/{$logId}");
        $this->assertEquals('250000.00', $recheck->json('data.amount')); // unchanged, still 50*5000
    }

    /**
     * The core confirmed business rule: Izin/Sakit do NOT deduct
     * monthly salary, only Alpa does (prorated by day).
     */
    public function test_monthly_salary_deducts_only_for_alpa_not_izin_or_sakit(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['payroll.create']);
        $employee = Employee::factory()->create([
            'company_id' => $company->id, 'pay_type' => 'monthly', 'monthly_salary' => 3000000,
        ]);

        // April 2026 has 30 days -> daily equivalent = 100.000.
        foreach ([1, 2, 3] as $day) { // 3 izin/sakit days — must NOT deduct
            Attendance::create([
                'company_id' => $company->id, 'employee_id' => $employee->id,
                'date' => "2026-04-0{$day}", 'status' => $day === 3 ? 'sakit' : 'izin',
            ]);
        }
        foreach ([10, 11] as $day) { // 2 alpa days — MUST deduct
            Attendance::create([
                'company_id' => $company->id, 'employee_id' => $employee->id,
                'date' => "2026-04-{$day}", 'status' => 'alpa',
            ]);
        }

        $response = $this->actingAs($owner)->postJson('/api/v1/payroll-periods/generate', [
            'year' => 2026, 'month' => 4,
        ]);
        $response->assertOk();

        $payslip = collect($response->json('data.payslips'))->firstWhere('employee_id', $employee->id);
        // 3.000.000 - (2 * 100.000) = 2.800.000
        $this->assertEquals('2800000.00', $payslip['gross_amount']);
        $this->assertEquals(2, $payslip['detail']['alpa_days']);
    }

    public function test_daily_wage_calculated_automatically_from_hadir_days(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['payroll.create']);
        $employee = Employee::factory()->create([
            'company_id' => $company->id, 'pay_type' => 'daily', 'daily_rate' => 100000,
        ]);

        foreach ([1, 2, 3, 4, 5] as $day) { // 5 hadir days
            Attendance::create([
                'company_id' => $company->id, 'employee_id' => $employee->id,
                'date' => "2026-05-0{$day}", 'status' => 'hadir',
            ]);
        }
        Attendance::create([ // 1 izin day — must NOT count toward daily pay
            'company_id' => $company->id, 'employee_id' => $employee->id,
            'date' => '2026-05-06', 'status' => 'izin',
        ]);

        $response = $this->actingAs($owner)->postJson('/api/v1/payroll-periods/generate', [
            'year' => 2026, 'month' => 5,
        ]);

        $payslip = collect($response->json('data.payslips'))->firstWhere('employee_id', $employee->id);
        $this->assertEquals('500000.00', $payslip['gross_amount']); // 5 * 100.000
    }

    public function test_piece_rate_payslip_sums_that_periods_work_logs(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['payroll.create', 'piece_work_log.create']);
        $employee = Employee::factory()->create(['company_id' => $company->id, 'pay_type' => 'piece_rate']);
        $workType = WorkType::factory()->create(['company_id' => $company->id, 'rate' => 5000]);

        $this->actingAs($owner)->postJson('/api/v1/piece-work-logs', [
            'employee_id' => $employee->id, 'work_type_id' => $workType->id,
            'date' => '2026-06-05', 'quantity' => 50,
        ])->assertCreated();
        $this->actingAs($owner)->postJson('/api/v1/piece-work-logs', [
            'employee_id' => $employee->id, 'work_type_id' => $workType->id,
            'date' => '2026-06-10', 'quantity' => 30,
        ])->assertCreated();

        $response = $this->actingAs($owner)->postJson('/api/v1/payroll-periods/generate', [
            'year' => 2026, 'month' => 6,
        ]);

        $payslip = collect($response->json('data.payslips'))->firstWhere('employee_id', $employee->id);
        $this->assertEquals('400000.00', $payslip['gross_amount']); // (50+30)*5000
    }

    public function test_regenerating_a_draft_period_recalculates_not_duplicates(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['payroll.create']);
        $employee = Employee::factory()->create([
            'company_id' => $company->id, 'pay_type' => 'daily', 'daily_rate' => 100000,
        ]);

        Attendance::create([
            'company_id' => $company->id, 'employee_id' => $employee->id,
            'date' => '2026-07-01', 'status' => 'hadir',
        ]);

        $this->actingAs($owner)->postJson('/api/v1/payroll-periods/generate', ['year' => 2026, 'month' => 7])->assertOk();

        // Attendance was corrected (per the "mudah dikoreksi" principle) — add another hadir day.
        Attendance::create([
            'company_id' => $company->id, 'employee_id' => $employee->id,
            'date' => '2026-07-02', 'status' => 'hadir',
        ]);

        $second = $this->actingAs($owner)->postJson('/api/v1/payroll-periods/generate', ['year' => 2026, 'month' => 7]);
        $second->assertOk();

        $this->assertDatabaseCount('payroll_periods', 1); // still one period
        $this->assertDatabaseCount('payslips', 1); // still one payslip for this employee, updated not duplicated
        $payslip = collect($second->json('data.payslips'))->firstWhere('employee_id', $employee->id);
        $this->assertEquals('200000.00', $payslip['gross_amount']); // now reflects 2 hadir days
    }

    public function test_cannot_regenerate_a_finalized_period(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['payroll.create', 'payroll.update']);
        Employee::factory()->create(['company_id' => $company->id, 'pay_type' => 'daily', 'daily_rate' => 100000]);

        $generated = $this->actingAs($owner)->postJson('/api/v1/payroll-periods/generate', ['year' => 2026, 'month' => 8]);
        $periodId = $generated->json('data.id');

        $this->actingAs($owner)->postJson("/api/v1/payroll-periods/{$periodId}/finalize")->assertOk();

        $again = $this->actingAs($owner)->postJson('/api/v1/payroll-periods/generate', ['year' => 2026, 'month' => 8]);
        $again->assertStatus(422);
    }

    /**
     * The kasbon integration: finalizing must actually reduce the
     * employee's outstanding loan balance, not just show a number.
     */
    public function test_finalizing_period_posts_loan_deduction_and_reduces_balance(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', [
            'payroll.create', 'payroll.update', 'employee_loan.create', 'employee_loan.view',
        ]);
        $employee = Employee::factory()->create([
            'company_id' => $company->id, 'pay_type' => 'daily', 'daily_rate' => 100000,
        ]);

        $loan = $this->actingAs($owner)->postJson('/api/v1/employee-loans', [
            'employee_id' => $employee->id, 'date' => '2026-08-01', 'amount' => 500000,
        ]);
        $loanId = $loan->json('data.id');

        Attendance::create([
            'company_id' => $company->id, 'employee_id' => $employee->id,
            'date' => '2026-09-01', 'status' => 'hadir',
        ]);
        Attendance::create([
            'company_id' => $company->id, 'employee_id' => $employee->id,
            'date' => '2026-09-02', 'status' => 'hadir',
        ]);
        Attendance::create([
            'company_id' => $company->id, 'employee_id' => $employee->id,
            'date' => '2026-09-03', 'status' => 'hadir',
        ]);
        // 3 hadir days * 100.000 = 300.000 gross.

        $generated = $this->actingAs($owner)->postJson('/api/v1/payroll-periods/generate', ['year' => 2026, 'month' => 9]);
        $periodId = $generated->json('data.id');
        $payslip = collect($generated->json('data.payslips'))->firstWhere('employee_id', $employee->id);

        $this->assertEquals('300000.00', $payslip['gross_amount']);
        $this->assertEquals('300000.00', $payslip['deduction_amount']); // capped at gross, loan has more (500k)
        $this->assertEquals('0.00', $payslip['net_amount']);

        // Before finalize: loan balance untouched.
        $beforeFinalize = $this->actingAs($owner)->getJson("/api/v1/employee-loans/{$loanId}");
        $this->assertEquals(500000.0, $beforeFinalize->json('data.remaining_balance'));

        $this->actingAs($owner)->postJson("/api/v1/payroll-periods/{$periodId}/finalize")->assertOk();

        // After finalize: loan balance reduced by the deduction actually posted.
        $afterFinalize = $this->actingAs($owner)->getJson("/api/v1/employee-loans/{$loanId}");
        $this->assertEquals(200000.0, $afterFinalize->json('data.remaining_balance')); // 500.000 - 300.000
    }

    public function test_worker_sees_only_their_own_payslip(): void
    {
        $company = Company::factory()->create();
        $owner = $this->makeUserWithRole($company, 'owner', ['payroll.create']);
        $worker = $this->makeUserWithRole($company, 'worker', ['payroll.view_own']);
        $employeeForWorker = Employee::factory()->create([
            'company_id' => $company->id, 'user_id' => $worker->id, 'pay_type' => 'daily', 'daily_rate' => 100000,
        ]);
        $otherEmployee = Employee::factory()->create([
            'company_id' => $company->id, 'pay_type' => 'daily', 'daily_rate' => 100000,
        ]);

        Attendance::create([
            'company_id' => $company->id, 'employee_id' => $employeeForWorker->id,
            'date' => '2026-10-01', 'status' => 'hadir',
        ]);
        Attendance::create([
            'company_id' => $company->id, 'employee_id' => $otherEmployee->id,
            'date' => '2026-10-01', 'status' => 'hadir',
        ]);

        $generated = $this->actingAs($owner)->postJson('/api/v1/payroll-periods/generate', ['year' => 2026, 'month' => 10]);
        $myPayslipId = collect($generated->json('data.payslips'))->firstWhere('employee_id', $employeeForWorker->id)['id'];
        $otherPayslipId = collect($generated->json('data.payslips'))->firstWhere('employee_id', $otherEmployee->id)['id'];

        $this->actingAs($worker)->getJson("/api/v1/payslips/{$myPayslipId}")->assertOk();
        $this->actingAs($worker)->getJson("/api/v1/payslips/{$otherPayslipId}")->assertForbidden();
    }
}
