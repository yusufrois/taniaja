<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByModulePermission;

class EmployeeLoanPolicy extends AuthorizesByModulePermission
{
    protected string $module = 'employee_loan';
}
