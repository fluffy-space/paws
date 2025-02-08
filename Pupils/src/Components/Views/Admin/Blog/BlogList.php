<?php

namespace Pupils\Components\Views\Admin\Blog;

use Pupils\Components\Guards\AdminGuard;
use SharedPaws\Models\Blog\BlogPostModel;
use Viewi\Components\BaseComponent;
use Viewi\Components\Attributes\Middleware;
use Viewi\UI\Components\Tables\TableColumn;

#[Middleware([AdminGuard::class])]
class BlogList extends BaseComponent
{
    public array $columns = [];

    public function __construct() {}

    public function init()
    {
        $this->setUpColumns();
    }

    public function setUpColumns()
    {
        $this->columns = [
            new TableColumn('Id'),
            new TableColumn('Title', null, 'BlogTitleColumn'),
            new TableColumn('Published', null, 'PublishedColumn'),
            new TableColumn('CreatedOn', 'Created', 'DateColumn'),
        ];
    }

    public function deleteMessage()
    {
        return fn(BlogPostModel $item) => "Are you sure you want to delete '{$item->Title}' with Id {$item->Id}?";
    }
}
