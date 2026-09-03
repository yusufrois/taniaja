<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByModulePermission;

class AssetPolicy extends AuthorizesByModulePermission
{
    protected string $module = 'asset';
}
