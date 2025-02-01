<?php

namespace Pupils\Components\Services\Layouts;

use Viewi\DI\Singleton;

#[Singleton]
class LayoutService
{
    public bool $miniSidebar = false;
    public bool $showMobileMenu = false;
    public bool $dark = false;
}
