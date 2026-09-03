<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByModulePermission;

class ActivityTemplatePolicy extends AuthorizesByModulePermission
{
    protected string $module = 'template';
}
