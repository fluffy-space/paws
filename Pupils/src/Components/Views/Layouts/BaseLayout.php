<?php

namespace Pupils\Components\Views\Layouts;

use Pupils\Components\Services\Layouts\LayoutService;
use Viewi\Components\BaseComponent;
use Viewi\Components\Lifecycle\OnMounted;
use Viewi\Components\Lifecycle\OnMounting;

class BaseLayout extends BaseComponent implements OnMounted, OnMounting
{
    use ManagesMetaTags;

    public function __construct(public LayoutService $layout) {}

    public function mounting()
    {
        $this->resetMeta();
    }

    public function mounted()
    {
        $this->mountMetaTags();
        $this->watchMeta();
    }
}
