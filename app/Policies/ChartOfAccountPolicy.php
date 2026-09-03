<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByModulePermission;

class ChartOfAccountPolicy extends AuthorizesByModulePermission
{
    protected string $module = 'accounting';
}
