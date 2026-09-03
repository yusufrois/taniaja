<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByModulePermission;

class CustomerPolicy extends AuthorizesByModulePermission
{
    protected string $module = 'customer';
}
