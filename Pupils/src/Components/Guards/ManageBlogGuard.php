<?php

namespace Pupils\Components\Guards;

use Viewi\DI\Singleton;

/** Admin guard requiring the ManageBlog capability. */
#[Singleton]
class ManageBlogGuard extends CapabilityGuard
{
    public function capability(): string
    {
        return 'ManageBlog';
    }
}
