<?php

namespace Pupils\Components\Guards;

use Viewi\DI\Singleton;

/** Admin guard requiring the ManageEmailTemplates capability. */
#[Singleton]
class ManageEmailTemplatesGuard extends CapabilityGuard
{
    public function capability(): string
    {
        return 'ManageEmailTemplates';
    }
}
