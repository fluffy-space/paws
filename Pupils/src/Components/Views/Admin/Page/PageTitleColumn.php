<?php

namespace Pupils\Components\Views\Admin\Page;

use SharedPaws\Models\Content\PageModel;
use Viewi\Components\Attributes\IncludeAlways;
use Viewi\Components\BaseComponent;

#[IncludeAlways]
class PageTitleColumn extends BaseComponent
{
    public $value = null;
    public ?PageModel $data = null;
}
