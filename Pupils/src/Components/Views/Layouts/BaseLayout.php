<?php

namespace Pupils\Components\Views\Layouts;

use Pupils\Components\Services\Layouts\LayoutService;
use Viewi\Components\BaseComponent;

class BaseLayout extends BaseComponent
{
    public string $title = 'Viewi';
    public ?string $description = null;
    public ?string $keywords = null;

    public function __construct(public LayoutService $layout) {}
}
