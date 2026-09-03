<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByModulePermission;

class ExpenseCategoryPolicy extends AuthorizesByModulePermission
{
    protected string $module = 'expense_category';
}
