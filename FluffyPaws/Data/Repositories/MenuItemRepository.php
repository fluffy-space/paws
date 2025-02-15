<?php

namespace FluffyPaws\Data\Repositories;

use DotDi\Attributes\Inject;
use Fluffy\Data\Repositories\BasePostgresqlRepository;
use FluffyPaws\Data\Entities\Menu\MenuItemEntity;
use FluffyPaws\Data\Entities\Menu\MenuItemEntityMap;

#[Inject(['entityType' => MenuItemEntity::class, 'entityMap' => MenuItemEntityMap::class])]
class MenuItemRepository extends BasePostgresqlRepository
{
}
