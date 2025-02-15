<?php

namespace Pupils\Components\Views\Admin\MenuItems;

use Pupils\Components\Guards\AdminGuard;
use SharedPaws\Models\MenuItem\MenuItemLocation;
use SharedPaws\Models\MenuItem\MenuItemModel;
use Viewi\Components\BaseComponent;
use Viewi\Components\Attributes\Middleware;
use Viewi\UI\Components\Tables\TableColumn;

#[Middleware([AdminGuard::class])]
class MenuItemList extends BaseComponent
{
    public array $columns = [];
    public int $language = 0;
    public int $locationId = 0;
    public function __construct(public string $area) {}

    public function init()
    {
        $menuLocation = new MenuItemLocation();
        if (!$menuLocation->hasArea($this->area)) {
            return;
        }
        $this->locationId = (new MenuItemLocation())->getLocationId($this->area);
        $this->setUpColumns();
    }

    public function setUpColumns()
    {
        $this->columns = [
            new TableColumn('Id'),
            new TableColumn('Title'),
            new TableColumn('Published', null, 'PublishedColumn'),
            new TableColumn('Order'),
            new TableColumn('CreatedOn', 'Created', 'DateColumn'),
        ];
    }

    public function deleteMessage()
    {
        return fn(MenuItemModel $item) => "Are you sure you want to delete '{$item->Link}' with Id {$item->Id}?";
    }
}
