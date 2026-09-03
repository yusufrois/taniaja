<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByModulePermission;

class CropPolicy extends AuthorizesByModulePermission
{
    protected string $module = 'crop';
}
