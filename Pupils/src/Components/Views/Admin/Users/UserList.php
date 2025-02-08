<?php

namespace Pupils\Components\Views\Admin\Users;

use Pupils\Components\Guards\AdminGuard;
use SharedPaws\Models\User\UserModel;
use Viewi\Components\BaseComponent;
use Viewi\Components\Attributes\Middleware;
use Viewi\UI\Components\Tables\TableColumn;

#[Middleware([AdminGuard::class])]
class UserList extends BaseComponent
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
            new TableColumn('FirstName'),
            new TableColumn('Active', null, 'PublishedColumn'),
            new TableColumn('CreatedOn', 'Created', 'DateColumn'),
        ];
    }

    public function deleteMessage()
    {
        return fn(UserModel $item) => "Are you sure you want to delete {$item->UserName} '{$item->FirstName} {$item->LastName}' with Id {$item->Id}?";
    }
}
