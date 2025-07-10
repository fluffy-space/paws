<?php

namespace FluffyPaws\Migrations\Menu;

use FluffyPaws\Data\Entities\Menu\MenuItemEntityMap;

class MenuItemsMigration01 extends MenuItemsBaseMigration
{
    public function getItems(): array
    {
        return [
            '/blog' => ['title' => 'Blog', 'location' =>  MenuItemEntityMap::LOCATION_HEADER]
        ];
    }
}
