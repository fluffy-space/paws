<?php

namespace Pupils\Components\Guards;

use Viewi\DI\Singleton;

/** Admin guard requiring the ManageLocalization capability. */
#[Singleton]
class ManageLocalizationGuard extends CapabilityGuard
{
    public function capability(): string
    {
        return 'ManageLocalization';
    }
}
