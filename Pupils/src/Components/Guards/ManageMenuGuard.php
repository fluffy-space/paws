<?php

namespace Pupils\Components\Guards;

use Viewi\DI\Singleton;

/** Admin guard requiring the ManageMenu capability. */
#[Singleton]
class ManageMenuGuard extends CapabilityGuard
{
    public function capability(): string
    {
        return 'ManageMenu';
    }
}
