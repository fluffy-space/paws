<?php

namespace Pupils\Components\Views\Admin\Page;

use Pupils\Components\Guards\AdminGuard;
use SharedPaws\Models\Content\PageModel;
use Viewi\Components\BaseComponent;
use Viewi\Components\Attributes\Middleware;
use Viewi\UI\Components\Tables\TableColumn;

#[Middleware([AdminGuard::class])]
class PageList extends BaseComponent
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
            new TableColumn('Title', null, 'PageTitleColumn'),
            new TableColumn('Published', null, 'PublishedColumn'),
            new TableColumn('CreatedOn', 'Created', 'DateColumn'),
        ];
    }

    public function deleteMessage()
    {
        return fn(PageModel $item) => "Are you sure you want to delete '{$item->Title}' with Id {$item->Id}?";
    }
}
