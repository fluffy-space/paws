<?php

namespace Pupils\Components\Views\Admin\Blog;

use SharedPaws\Models\Blog\BlogPostModel;
use Viewi\Components\Attributes\IncludeAlways;
use Viewi\Components\BaseComponent;

#[IncludeAlways]
class BlogTitleColumn extends BaseComponent
{
    public $value = null;
    public ?BlogPostModel $data = null;
}
