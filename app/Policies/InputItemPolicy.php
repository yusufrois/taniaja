<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByModulePermission;

class InputItemPolicy extends AuthorizesByModulePermission
{
    protected string $module = 'input_item';
}
