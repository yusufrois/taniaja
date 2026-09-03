<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;
use App\Policies\Concerns\AuthorizesByModulePermission;

class ExpensePolicy extends AuthorizesByModulePermission
{
    protected string $module = 'expense';

    /**
     * Separate from create/update — matches the Role & Permission Matrix
     * distinction between "Expense (input)" and "Expense (approve)":
     * Supervisor can input an expense but not approve it, while Finance
     * can do both.
     */
    public function approve(User $user, Expense $expense): bool
    {
        return $user->hasPermission('expense.approve');
    }
}
