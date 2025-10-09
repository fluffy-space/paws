<?php

namespace Pupils\Components\Views\Layouts;

use Viewi\Components\BaseComponent;
use Viewi\Components\Lifecycle\OnMounted;
use Viewi\Components\Lifecycle\OnMounting;

class PageLayout extends BaseComponent implements OnMounted, OnMounting
{
    use ManagesMetaTags;

    public bool $fluid = false;

    public function mounting()
    {
        $this->fluid = false;
        $this->resetMeta();
    }

    public function mounted()
    {
        $this->mountMetaTags();
    }
}
