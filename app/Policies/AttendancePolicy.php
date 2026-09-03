<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

/**
 * Does NOT extend AuthorizesByModulePermission — that base class's
 * "view_own" checks $model->created_by === $user->id, which is wrong
 * here. For Attendance, "own" means "this record is ABOUT me" (i.e.
 * $model->employee_id matches MY linked Employee), regardless of WHO
 * recorded it — a Supervisor might record a Worker's attendance, but
 * the Worker (not the Supervisor) is who should see it as "theirs".
 */
class AttendancePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('attendance.view')
            || $user->hasPermission('attendance.view_own');
    }

    public function view(User $user, Attendance $attendance): bool
    {
        if ($user->hasPermission('attendance.view')) {
            return true;
        }

        if ($user->hasPermission('attendance.view_own')) {
            return $attendance->employee_id === $user->employee?->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('attendance.create');
    }

    public function update(User $user, Attendance $attendance): bool
    {
        return $user->hasPermission('attendance.update');
    }

    public function delete(User $user, Attendance $attendance): bool
    {
        return $user->hasPermission('attendance.delete');
    }
}
