<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByModulePermission;

class CapitalTransactionPolicy extends AuthorizesByModulePermission
{
    protected string $module = 'capital';
}
