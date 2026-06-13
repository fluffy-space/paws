<?php

namespace Pupils\Components\Guards;

use Viewi\DI\Singleton;

/** Admin guard requiring the ManagePages capability. */
#[Singleton]
class ManagePagesGuard extends CapabilityGuard
{
    public function capability(): string
    {
        return 'ManagePages';
    }
}
