<?php

namespace Pupils\Components\Views\Layouts\Footer;

use SharedPaws\Models\MenuItem\MenuItemLocation;
use SharedPaws\Models\MenuItem\MenuItemModel;
use Viewi\Components\BaseComponent;
use Viewi\Components\Http\HttpClient;

class BaseFooter extends BaseComponent
{
    public array $menuItems = [];

    public function __construct(private HttpClient $http) {}

    public function init()
    {
        $location = (new MenuItemLocation())->getLocationId('footer');
        $this->http->get("/api/menu/$location")->then(function (array $menuItems) {
            $columns = [];
            /** @var MenuItemModel[]  $menuItems  **/
            foreach ($menuItems as $item) {
                if (!isset($columns[$item->Column])) {
                    $columns[$item->Column] = [];
                }
                $columns[$item->Column][] = $item;
            }

            $this->menuItems = $columns;
        }, function () {
            // error
        });
    }
}
