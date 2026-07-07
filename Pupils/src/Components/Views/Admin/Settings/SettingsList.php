<?php

namespace Pupils\Components\Views\Admin\Settings;

use Pupils\Components\Guards\HasCapability;
use SharedPaws\Models\Settings\SettingModel;
use SharedPaws\Models\Settings\SettingValidation;
use Viewi\Components\Attributes\Middleware;
use Viewi\Components\BaseComponent;
use Viewi\UI\Components\Tables\TableColumn;

/**
 * /admin/settings — runtime settings store (ManageSettings / SuperAdmin). A
 * regular admin list with inline editing (like the string-resources page): the
 * value is edited in place with a Type-appropriate editor. Admin-created dynamic
 * settings can also be added/removed; code-declared ones edit value only.
 */
#[Middleware([[HasCapability::class, 'ManageSettings']])]
class SettingsList extends BaseComponent
{
    public array $columns = [];
    public array $types = ['string', 'boolean', 'number', 'json', 'date', 'dropdown', 'multiselect'];

    public function __construct() {}

    public function init()
    {
        $this->setUpColumns();
    }

    public function setUpColumns()
    {
        $this->columns = [
            new TableColumn('Key'),
            //new TableColumn('Type'),
            //new TableColumn('Group'),
            new TableColumn('Value'),
        ];
    }

    public function deleteMessage()
    {
        return fn(SettingModel $item) => "Delete the setting '{$item->Key}'?";
    }

    public function getValidation()
    {
        return fn($item) => new SettingValidation($item);
    }

    public function getNewItem()
    {
        return fn() => new SettingModel();
    }
}
