<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByModulePermission;

class VarietyPolicy extends AuthorizesByModulePermission
{
    protected string $module = 'variety';
}
