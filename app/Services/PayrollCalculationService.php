<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeLoanDeduction;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\PieceWorkLog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The payroll engine — implements the 3 pay-type rules confirmed in
 * discussion:
 * - monthly: Izin/Sakit NOT deducted, Alpa IS deducted (prorated by
 *   day) — see calculateMonthly()
 * - daily: automatic from attendance (hadir days × daily_rate)
 * - piece_rate: sum of that period's PieceWorkLog.amount (already
 *   frozen per-entry, see PieceWorkLog migration)
 */
class PayrollCalculationService
{
    /**
     * Computes (but does NOT save) one employee's figures for a
     * period. Called by generateForPeriod() below, which does the
     * actual upsert — kept separate so the calculation itself is easy
     * to unit-test independent of persistence.
     */
    public function calculateForEmployee(Employee $employee, int $year, int $month): array
    {
        return match ($employee->pay_type) {
            'monthly' => $this->calculateMonthly($employee, $year, $month),
            'daily' => $this->calculateDaily($employee, $year, $month),
            'piece_rate' => $this->calculatePieceRate($employee, $year, $month),
            default => [
                'gross_amount' => 0,
                'detail' => ['error' => 'pay_type belum diatur untuk pegawai ini'],
            ],
        };
    }

    /**
     * Prorate basis: monthly_salary ÷ total calendar days in that
     * month. This is a simplifying assumption (not, say, a fixed
     * 26-working-day convention) — documented explicitly since it's a
     * real business-rule choice, not an obvious universal standard.
     */
    private function calculateMonthly(Employee $employee, int $year, int $month): array
    {
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
        $dailyEquivalent = round((float) $employee->monthly_salary / $daysInMonth, 2);

        $alpaDays = Attendance::where('employee_id', $employee->id)
            ->whereYear('date', $year)->whereMonth('date', $month)
            ->where('status', 'alpa')->count();

        $deduction = round($dailyEquivalent * $alpaDays, 2);
        $gross = max(0, round((float) $employee->monthly_salary - $deduction, 2));

        return [
            'gross_amount' => $gross,
            'detail' => [
                'monthly_salary' => (float) $employee->monthly_salary,
                'days_in_month' => $daysInMonth,
                'daily_equivalent' => $dailyEquivalent,
                'alpa_days' => $alpaDays,
                'alpa_deduction' => $deduction,
            ],
        ];
    }

    private function calculateDaily(Employee $employee, int $year, int $month): array
    {
        $hadirDays = Attendance::where('employee_id', $employee->id)
            ->whereYear('date', $year)->whereMonth('date', $month)
            ->where('status', 'hadir')->count();

        $gross = round($hadirDays * (float) $employee->daily_rate, 2);

        return [
            'gross_amount' => $gross,
            'detail' => [
                'hadir_days' => $hadirDays,
                'daily_rate' => (float) $employee->daily_rate,
            ],
        ];
    }

    private function calculatePieceRate(Employee $employee, int $year, int $month): array
    {
        $logs = PieceWorkLog::where('employee_id', $employee->id)
            ->whereYear('date', $year)->whereMonth('date', $month)
            ->with('workType')
            ->get();

        $gross = round((float) $logs->sum('amount'), 2);

        return [
            'gross_amount' => $gross,
            'detail' => [
                'entries' => $logs->map(fn ($log) => [
                    'work_type' => $log->workType?->name,
                    'date' => $log->date->toDateString(),
                    'quantity' => (float) $log->quantity,
                    'rate' => (float) $log->rate,
                    'amount' => (float) $log->amount,
                ])->all(),
            ],
        ];
    }

    /**
     * Draft-generates (or regenerates) every active, pay_type-configured
     * employee's Payslip for this period. Safe to call repeatedly WHILE
     * DRAFT — e.g. after correcting an Attendance mistake — since it
     * only ever recomputes, never touches loan balances (that only
     * happens at finalize()). Aborts if the period is already finalized.
     */
    public function generateForPeriod(int $companyId, int $year, int $month): PayrollPeriod
    {
        $period = PayrollPeriod::firstOrCreate(
            ['company_id' => $companyId, 'year' => $year, 'month' => $month],
            ['status' => 'draft']
        );

        abort_if($period->status === 'finalized', 422, 'Periode ini sudah difinalisasi, tidak bisa digenerate ulang.');

        $employees = Employee::where('company_id', $companyId)
            ->where('status', 'active')
            ->whereNotNull('pay_type')
            ->get();

        foreach ($employees as $employee) {
            $result = $this->calculateForEmployee($employee, $year, $month);
            $gross = $result['gross_amount'];

            // Preview only — does NOT create EmployeeLoanDeduction rows
            // yet (that's finalize()'s job), so regenerating a draft
            // never double-counts a deduction.
            $outstandingLoan = $employee->totalOutstandingLoans();
            $deduction = min($gross, $outstandingLoan);
            $net = round($gross - $deduction, 2);

            Payslip::updateOrCreate(
                ['payroll_period_id' => $period->id, 'employee_id' => $employee->id],
                [
                    'company_id' => $companyId,
                    'pay_type' => $employee->pay_type,
                    'gross_amount' => $gross,
                    'deduction_amount' => $deduction,
                    'net_amount' => $net,
                    'detail' => $result['detail'],
                ]
            );
        }

        return $period->fresh('payslips');
    }

    /**
     * Locks the period and actually posts loan deductions — the moment
     * EmployeeLoanDeduction rows get created. Applies each payslip's
     * deduction_amount across the employee's outstanding loans OLDEST
     * FIRST (FIFO), since a person may have more than one loan open.
     */
    public function finalizePeriod(PayrollPeriod $period, int $finalizedByUserId): PayrollPeriod
    {
        abort_if($period->status === 'finalized', 422, 'Periode ini sudah difinalisasi sebelumnya.');

        DB::transaction(function () use ($period, $finalizedByUserId) {
            foreach ($period->payslips as $payslip) {
                if ($payslip->deduction_amount <= 0) {
                    continue;
                }

                $remainingToDeduct = (float) $payslip->deduction_amount;

                $loans = $payslip->employee->loans()->orderBy('date')->get();

                foreach ($loans as $loan) {
                    if ($remainingToDeduct <= 0) {
                        break;
                    }

                    $loanRemaining = $loan->remainingBalance();

                    if ($loanRemaining <= 0) {
                        continue;
                    }

                    $applyAmount = min($loanRemaining, $remainingToDeduct);

                    EmployeeLoanDeduction::create([
                        'company_id' => $period->company_id,
                        'employee_loan_id' => $loan->id,
                        'payslip_id' => $payslip->id,
                        'amount' => $applyAmount,
                    ]);

                    $remainingToDeduct = round($remainingToDeduct - $applyAmount, 2);
                }
            }

            $period->update([
                'status' => 'finalized',
                'finalized_at' => now(),
                'finalized_by' => $finalizedByUserId,
            ]);
        });

        return $period->fresh('payslips');
    }
}
