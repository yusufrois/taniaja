<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByModulePermission;

/**
 * Governs both Activity (ad hoc + completion records) and Schedule
 * mutations (complete/skip) — both are "run/log an activity" in the
 * Role & Permission Matrix, so they share the same 'activity' module
 * permission rather than needing a separate SchedulePolicy.
 */
class ActivityPolicy extends AuthorizesByModulePermission
{
    protected string $module = 'activity';
}
