<?php

namespace Pupils\Components\Views\Layouts;

use Viewi\Components\BaseComponent;

class PageLayout extends BaseComponent
{
    use HasMetaTags;

    public bool $fluid = false;

    public function mounting()
    {
        $this->fluid = false;
    }
}
