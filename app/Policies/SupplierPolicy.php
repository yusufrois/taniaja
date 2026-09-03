<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByModulePermission;

class SupplierPolicy extends AuthorizesByModulePermission
{
    protected string $module = 'supplier';
}
