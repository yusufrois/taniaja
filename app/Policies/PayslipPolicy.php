<?php

namespace App\Policies;

use App\Models\Payslip;
use App\Models\User;

/**
 * Does NOT extend AuthorizesByModulePermission — same reasoning as
 * AttendancePolicy: "own" means "this payslip is ABOUT me"
 * ($model->employee_id matches my linked Employee), not who created
 * it (payslips are always system-generated anyway, never "created_by"
 * a person in the first place).
 */
class PayslipPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('payroll.view')
            || $user->hasPermission('payroll.view_own');
    }

    public function view(User $user, Payslip $payslip): bool
    {
        if ($user->hasPermission('payroll.view')) {
            return true;
        }

        if ($user->hasPermission('payroll.view_own')) {
            return $payslip->employee_id === $user->employee?->id;
        }

        return false;
    }
}
