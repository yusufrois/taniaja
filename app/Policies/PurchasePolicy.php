<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByModulePermission;

class PurchasePolicy extends AuthorizesByModulePermission
{
    protected string $module = 'purchase';
}
