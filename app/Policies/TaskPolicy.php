<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

/**
 * Custom (not AuthorizesByModulePermission) — "own" here means EITHER
 * I'm the assignee OR I'm the one who assigned it, not just
 * created_by. A Supervisor giving out a task should see it as much as
 * the person who received it.
 */
class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('task.view') || $user->hasPermission('task.view_own');
    }

    public function view(User $user, Task $task): bool
    {
        if ($user->hasPermission('task.view')) {
            return true;
        }

        if ($user->hasPermission('task.view_own')) {
            return $task->assigned_to === $user->id || $task->assigned_by === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('task.create');
    }

    /**
     * Editing title/description/due_date is the ASSIGNER's (or an
     * admin's) call — NOT automatically granted to the assignee just
     * because they can view it. Checking off steps / updating status
     * is a SEPARATE, lighter action — see TaskController, which uses
     * view() (not update()) for that, since the whole point of
     * assigning someone a task is letting them progress it themselves.
     */
    public function update(User $user, Task $task): bool
    {
        return $user->hasPermission('task.update') || $task->assigned_by === $user->id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->hasPermission('task.delete') || $task->assigned_by === $user->id;
    }
}
