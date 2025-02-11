<?php

namespace Pupils\Components\Views\Admin\Localization;

use Pupils\Components\Guards\AdminGuard;
use SharedPaws\Models\Localization\LanguageModel;
use Viewi\Components\BaseComponent;
use Viewi\Components\Attributes\Middleware;
use Viewi\UI\Components\Tables\TableColumn;

#[Middleware([AdminGuard::class])]
class LanguageList extends BaseComponent
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
            new TableColumn('Name'),
            new TableColumn('LanguageCulture', 'Culture'),
            new TableColumn('Published', null, 'PublishedColumn'),
            new TableColumn('CreatedOn', 'Created', 'DateColumn'),
        ];
    }

    public function deleteMessage()
    {
        return fn(LanguageModel $item) => "Are you sure you want to delete '{$item->Name}' with Id {$item->Id}?";
    }
}
