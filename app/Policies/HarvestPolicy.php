<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByModulePermission;

class HarvestPolicy extends AuthorizesByModulePermission
{
    protected string $module = 'harvest';
}
