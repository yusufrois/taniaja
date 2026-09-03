<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByModulePermission;

class PlantLossPolicy extends AuthorizesByModulePermission
{
    protected string $module = 'plant_loss';
}
