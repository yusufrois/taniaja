<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByModulePermission;

class GreenhousePolicy extends AuthorizesByModulePermission
{
    protected string $module = 'greenhouse';
}
