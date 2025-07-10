<?php

namespace FluffyPaws\Migrations\Menu;

use Fluffy\Data\Repositories\MigrationRepository;
use Fluffy\Migrations\BaseMigration;
use FluffyPaws\Data\Entities\Menu\MenuItemEntity;
use FluffyPaws\Data\Entities\Menu\MenuItemEntityMap;
use FluffyPaws\Data\Repositories\MenuItemRepository;

abstract class MenuItemsBaseMigration extends BaseMigration
{
    function __construct(MigrationRepository $MigrationHistoryRepository, private MenuItemRepository $menuItemRepository)
    {
        parent::__construct($MigrationHistoryRepository);
    }

    protected function getKey(MenuItemEntity $entity): string
    {
        return "{$entity->Location}_{$entity->Link}";
    }

    public function up()
    {
        $entities = $this->menuItemRepository->search(size: null, returnCount: false);
        $items = $entities['list'];
        $models = [];
        $models = array_reduce($items, function (array $models, MenuItemEntity $entity) {
            $models[$this->getKey($entity)] = $entity;
            return $models;
        }, $models);
        $toAdd = $this->getItems();
        $added = 0;
        $skipped = 0;
        foreach ($toAdd as $key => $value) {
            if (!isset($models[$key])) {
                $menuItem = new MenuItemEntity();
                $menuItem->Link = $key;
                $menuItem->Title = $value['title'];
                $menuItem->Location = $value['location'];
                $menuItem->Published = true;
                $this->menuItemRepository->create($menuItem);
                $added++;
            } else {
                $skipped++;
            }
        }
        echo "MenuItemsMigration: $added added, skipped $skipped" . PHP_EOL;
    }

    public function down() {}

    public abstract function getItems(): array;
}
