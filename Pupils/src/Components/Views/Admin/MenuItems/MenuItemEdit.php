<?php

namespace Pupils\Components\Views\Admin\MenuItems;

use Pupils\Components\Guards\AdminGuard;
use Pupils\Components\Views\Admin\EditPage\EditPage;
use SharedPaws\Models\BaseModel;
use SharedPaws\Models\MenuItem\MenuItemLocation;
use SharedPaws\Models\MenuItem\MenuItemModel;
use SharedPaws\Models\MenuItem\MenuItemValidation;
use SharedPaws\Validation\IValidationRules;
use Viewi\Components\Attributes\Middleware;
use Viewi\Components\Http\HttpClient;
use Viewi\Components\Routing\ClientRoute;
use Viewi\UI\Components\Alerts\AlertService;

/**
 * 
 * @package Pupils\Components\Views\Admin\MenuItems
 * @property MenuItemModel $item
 */
#[Middleware([AdminGuard::class])]
class MenuItemEdit extends EditPage
{
    public string $segment = 'menu';
    public ?string $apiUrl = 'menu';
    public bool $changePassword = false;
    public string $name = "Menu item";
    public array $locations = [];
    public int $locationId = 0;

    public function __construct(
        public string $area,
        public int $id,
        private HttpClient $http,
        private AlertService $messages,
        private ClientRoute $route
    ) {
        parent::__construct($id, $http, $messages, $route);
    }

    public function init()
    {
        $this->segment = "menu/{$this->area}";
        $menuLocation = new MenuItemLocation();
        if (!$menuLocation->hasArea($this->area)) {
            return;
        }
        $this->locationId = $menuLocation->getLocationId($this->area);
        $this->locations = $menuLocation->getLocations();
        parent::init();
    }

    public function getValidation(BaseModel $item): ?IValidationRules
    {
        return new MenuItemValidation($item);
    }

    public function getNewItem(): BaseModel
    {
        $item = new MenuItemModel();
        $item->Location = $this->locationId;
        return $item;
    }
}
