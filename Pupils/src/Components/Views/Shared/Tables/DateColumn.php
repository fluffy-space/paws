<?php

namespace Pupils\Components\Views\Shared\Tables;

use Viewi\Components\Attributes\IncludeAlways;
use Viewi\Components\BaseComponent;

#[IncludeAlways]
class DateColumn extends BaseComponent
{
    public $value = null;
    public $data = null;
}
