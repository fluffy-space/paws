<?php

namespace FluffyPaws\Migrations\Menu;

use Fluffy\Data\Entities\CommonMap;
use Fluffy\Data\Repositories\MigrationRepository;
use Fluffy\Migrations\BaseMigration;
use FluffyPaws\Data\Entities\Menu\MenuItemEntityMap;
use FluffyPaws\Data\Repositories\MenuItemRepository;

class MenuItemMigration extends BaseMigration
{
    function __construct(MigrationRepository $MigrationHistoryRepository, private MenuItemRepository $menuItemRepository)
    {
        parent::__construct($MigrationHistoryRepository);
    }

    public function up()
    {
        $this->menuItemRepository->createTable(
            [
                'Id' => CommonMap::$Id,
                'Title' => CommonMap::$TextCaseInsensitive,
                'Link' => CommonMap::$TextCaseInsensitiveNull,
                'NewTab' => CommonMap::$Boolean,
                'Published' => CommonMap::$Boolean,
                'LinkClass' => CommonMap::$TextCaseInsensitiveNull,
                'Icon' => CommonMap::$TextCaseInsensitiveNull,
                'Order' => CommonMap::$Int,
                MenuItemEntityMap::PROPERTY_Location => CommonMap::$Int,
                'Row' => CommonMap::$Int,
                'Column' => CommonMap::$Int,

                'CreatedOn' => CommonMap::$MicroDateTime,
                'CreatedBy' => CommonMap::$VarChar255Null,
                'UpdatedOn' => CommonMap::$MicroDateTime,
                'UpdatedBy' => CommonMap::$VarChar255Null,
            ],
            ['Id'],
            [
                'IX_Location' => [
                    'Columns' => [MenuItemEntityMap::PROPERTY_Location],
                    'Unique' => false
                ]
            ],
            []
        );
    }

    public function down()
    {
        $this->menuItemRepository->dropTable(true, true);
    }
}
