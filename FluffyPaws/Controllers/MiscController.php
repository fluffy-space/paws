<?php

namespace FluffyPaws\Controllers;

use Fluffy\Controllers\BaseController;
use Fluffy\Data\Mapper\IMapper;
use Fluffy\Swoole\Cache\CacheManager;
use FluffyPaws\Data\Entities\Menu\MenuItemEntityMap;
use FluffyPaws\Data\Repositories\MenuItemRepository;
use SharedPaws\Models\MenuItem\MenuItemModel;

class MiscController extends BaseController
{
    public function __construct(
        protected IMapper $mapper,
        protected MenuItemRepository $menuItems,
        protected CacheManager $cache
    ) {}

    public function GetMenuItems(int $location)
    {
        $cacheKey = sprintf(MenuItemEntityMap::CACHE_KEY, $location);
        $models = $this->cache->get($cacheKey);
        if ($models === null) {
            $models = $this->cache->set($cacheKey, function () use ($location) {
                $items = $this->menuItems->search(
                    [
                        [MenuItemEntityMap::PROPERTY_Location, $location],
                        [MenuItemEntityMap::PROPERTY_Published, true]
                    ],
                    ['Row' => 1, 'Column' => 1, MenuItemEntityMap::PROPERTY_Order => 1, MenuItemEntityMap::PROPERTY_Id => 1]
                );
                return array_map(fn($entity) => $this->mapper->map(MenuItemModel::class, $entity), $items['list']);
            });
        }
        return $models;
    }
}
