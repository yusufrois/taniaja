<?php

namespace App\Policies;

use App\Policies\Concerns\AuthorizesByModulePermission;

class DeliveryNotePolicy extends AuthorizesByModulePermission
{
    protected string $module = 'delivery_note';
}
