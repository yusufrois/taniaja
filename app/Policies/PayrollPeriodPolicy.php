<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByModulePermission;

/**
 * 'payroll.create' = generate a period's payslips,
 * 'payroll.update' = finalize a period — see PayrollController.
 */
class PayrollPeriodPolicy extends AuthorizesByModulePermission
{
    protected string $module = 'payroll';
}
