<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByModulePermission;

class AssetCategoryPolicy extends AuthorizesByModulePermission
{
    protected string $module = 'asset_category';
}
