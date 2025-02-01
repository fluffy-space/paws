<?php

namespace Pupils\Components\Views\Admin\Layouts;

use Pupils\Components\Services\Layouts\LayoutService;
use Viewi\Components\BaseComponent;

class AdminHeader extends BaseComponent
{
    public function __construct(public LayoutService $layout)
    {
    }

    public function init()
    {
        <<<'javascript'
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            $this.layout.dark = true;
        }
        javascript;
    }
}
