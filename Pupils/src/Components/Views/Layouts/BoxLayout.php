<?php

namespace Pupils\Components\Views\Layouts;

use Viewi\Components\BaseComponent;
use Viewi\Components\Lifecycle\OnMounted;
use Viewi\Components\Lifecycle\OnMounting;

class BoxLayout extends BaseComponent implements OnMounted, OnMounting
{
    use ManagesMetaTags;

    public function mounting()
    {
        $this->resetMeta();
    }

    public function mounted()
    {
        $this->mountMetaTags();
    }
}
