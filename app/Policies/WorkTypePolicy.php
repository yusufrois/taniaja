<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByModulePermission;

class WorkTypePolicy extends AuthorizesByModulePermission
{
    protected string $module = 'work_type';
}
