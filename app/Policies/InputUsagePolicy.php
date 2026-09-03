<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByModulePermission;

class InputUsagePolicy extends AuthorizesByModulePermission
{
    protected string $module = 'input_usage';
}
