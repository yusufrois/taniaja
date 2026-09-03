<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByModulePermission;

class EmployeePolicy extends AuthorizesByModulePermission
{
    protected string $module = 'employee';
}
