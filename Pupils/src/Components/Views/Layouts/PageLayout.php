<?php

namespace Pupils\Components\Views\Layouts;

use Viewi\Components\BaseComponent;

class PageLayout extends BaseComponent
{
    public string $title = '';
    public ?string $description = null;
    public ?string $keywords = null;
    public bool $fluid = false;

    public function mounting()
    {
        $this->fluid = false;
    }
}
