<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByModulePermission;

class PieceWorkLogPolicy extends AuthorizesByModulePermission
{
    protected string $module = 'piece_work_log';
}
