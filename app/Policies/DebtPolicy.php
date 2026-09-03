<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByModulePermission;

class DebtPolicy extends AuthorizesByModulePermission
{
    protected string $module = 'debt';
}
