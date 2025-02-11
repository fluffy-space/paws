<?php

namespace Pupils\Components\Views\Admin\Localization;

use SharedPaws\Models\Localization\LocaleResourceModel;
use SharedPaws\Models\Localization\LocaleResourceValidation;
use Viewi\Components\BaseComponent;
use Viewi\UI\Components\Tables\TableColumn;

class StringResourcesList extends BaseComponent
{
    public array $columns = [];
    public int $language = 0;

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
            new TableColumn('Value'),
        ];
    }

    public function deleteMessage()
    {
        return fn(LocaleResourceModel $item) => "Are you sure you want to delete '{$item->Name}' with Id {$item->Id}?";
    }

    public function getValidation()
    {
        return fn($item) => new LocaleResourceValidation($item);
    }

    public function getNewItem()
    {
        return fn() => new LocaleResourceModel($this->language);
    }
}
