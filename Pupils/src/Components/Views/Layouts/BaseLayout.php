<?php

namespace Pupils\Components\Views\Layouts;

use Pupils\Components\Services\Layouts\LayoutService;
use Viewi\Components\BaseComponent;
use Viewi\Components\Lifecycle\OnMounted;

class BaseLayout extends BaseComponent implements OnMounted
{
    use ManagesMetaTags;

    public function __construct(public LayoutService $layout) {}

    public function mounted()
    {
        $this->mountMetaTags();
    }
}
