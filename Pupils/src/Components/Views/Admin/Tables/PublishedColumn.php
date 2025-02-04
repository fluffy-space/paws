<?php

namespace Pupils\Components\Views\Admin\Tables;

use Viewi\Components\Attributes\IncludeAlways;
use Viewi\Components\BaseComponent;

#[IncludeAlways]
class PublishedColumn extends BaseComponent
{
    public $value = null;
    public $data = null;
}
