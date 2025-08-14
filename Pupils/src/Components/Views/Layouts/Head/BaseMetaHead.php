<?php

namespace Pupils\Components\Views\Layouts\Head;

use Viewi\Components\BaseComponent;

class BaseMetaHead extends BaseComponent
{
    public string $title = 'Viewi';
    public ?string $description = null;
    public ?string $keywords = null;
}
