<?php

namespace Pupils\Components\Guards;

use Viewi\DI\Singleton;

/** Admin guard requiring the ManageUsers capability. */
#[Singleton]
class ManageUsersGuard extends CapabilityGuard
{
    public function capability(): string
    {
        return 'ManageUsers';
    }
}
