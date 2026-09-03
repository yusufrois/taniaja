<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByModulePermission;

class SeasonPolicy extends AuthorizesByModulePermission
{
    protected string $module = 'season';
}
