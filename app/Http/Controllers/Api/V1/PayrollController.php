<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Concerns\LogsAudit;
use App\Http\Controllers\Controller;
use App\Http\Requests\GeneratePayrollRequest;
use App\Http\Resources\PayrollPeriodResource;
use App\Http\Resources\PayslipResource;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Services\PayrollCalculationService;

class PayrollController extends Controller
{
    use LogsAudit;

    public function index()
    {
        $this->authorize('viewAny', PayrollPeriod::class);

        return PayrollPeriodResource::collection(
            PayrollPeriod::orderByDesc('year')->orderByDesc('month')->paginate(12)
        );
    }

    public function show(PayrollPeriod $payroll_period)
    {
        $this->authorize('view', $payroll_period);

        return new PayrollPeriodResource($payroll_period->load('payslips.employee'));
    }

    /**
     * Generates (or, while still draft, REGENERATES) every eligible
     * employee's payslip for a period. Safe to call again after fixing
     * an Attendance mistake — see PayrollCalculationService's docblock.
     */
    public function generate(GeneratePayrollRequest $request, PayrollCalculationService $service)
    {
        $this->authorize('create', PayrollPeriod::class);

        $period = $service->generateForPeriod(
            $request->user()->company_id,
            $request->validated('year'),
            $request->validated('month')
        );

        $this->logAudit('payroll.generate', $period, null, ['year' => $period->year, 'month' => $period->month]);

        return new PayrollPeriodResource($period->load('payslips.employee'));
    }

    /**
     * Locks the period — no more regeneration after this, and this is
     * the moment kasbon deductions actually get posted against
     * EmployeeLoan balances (see PayrollCalculationService::finalizePeriod()).
     */
    public function finalize(PayrollPeriod $payroll_period, PayrollCalculationService $service)
    {
        $this->authorize('update', $payroll_period);

        $period = $service->finalizePeriod($payroll_period, auth()->id());

        $this->logAudit('payroll.finalize', $period, ['status' => 'draft'], ['status' => 'finalized']);

        return new PayrollPeriodResource($period->load('payslips.employee'));
    }

    public function showPayslip(Payslip $payslip)
    {
        $this->authorize('view', $payslip);

        return new PayslipResource($payslip->load('employee'));
    }
}
