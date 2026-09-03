<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByModulePermission;

class GradePolicy extends AuthorizesByModulePermission
{
    protected string $module = 'grade';
}
